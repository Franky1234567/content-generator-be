<?php

namespace App\Http\Controllers;

use App\Models\ContentGeneration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ContentGenerationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->contentGenerations()->latest();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('topic', 'like', "%{$request->search}%")
                  ->orWhere('content_type', 'like', "%{$request->search}%")
                  ->orWhere('keywords', 'like', "%{$request->search}%");
            });
        }

        if ($request->type) {
            $query->where('content_type', $request->type);
        }

        return response()->json($query->paginate(10));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'content_type' => 'required|string',
            'topic' => 'required|string|max:500',
            'keywords' => 'nullable|string|max:500',
            'target_audience' => 'nullable|string|max:255',
            'tone' => 'required|string',
            'language' => 'required|string|max:100',
        ]);

        $prompt = $this->buildPrompt($request->all());
        $generatedContent = $this->callGemini($prompt);

        $wordCount = str_word_count(strip_tags($generatedContent));

        $generation = $request->user()->contentGenerations()->create([
            'content_type' => $request->content_type,
            'topic' => $request->topic,
            'keywords' => $request->keywords,
            'target_audience' => $request->target_audience,
            'tone' => $request->tone,
            'language' => $request->language,
            'generated_content' => $generatedContent,
            'word_count' => $wordCount,
        ]);

        return response()->json($generation, 201);
    }

    public function show(Request $request, ContentGeneration $generation): JsonResponse
    {
        if ($generation->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($generation);
    }

    public function destroy(Request $request, ContentGeneration $generation): JsonResponse
    {
        if ($generation->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $generation->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    private function buildPrompt(array $data): string
    {
        $prompt = "You are a professional content writer. Generate a {$data['content_type']} about the following:\n\n";
        $prompt .= "Topic: {$data['topic']}\n";

        if (!empty($data['keywords'])) {
            $prompt .= "Keywords to include: {$data['keywords']}\n";
        }

        if (!empty($data['target_audience'])) {
            $prompt .= "Target audience: {$data['target_audience']}\n";
        }

        $prompt .= "Tone: {$data['tone']}\n\n";
        $language = $data['language'] ?? 'Bahasa Indonesia';
        $prompt .= "Write engaging, well-structured content. Use proper formatting with paragraphs. Do not include any preamble or explanation, just the content itself. Write the content in {$language}.";

        return $prompt;
    }

    private function callGemini(string $prompt): string
    {
        // Primary: Groq (fast, free)
        $groqKey = config('services.groq.key');
        $response = Http::timeout(30)->withHeaders([
            'Authorization' => "Bearer {$groqKey}",
        ])->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => 'llama-3.3-70b-versatile',
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'max_tokens' => 2048,
        ]);

        if ($response->successful()) {
            return $response->json('choices.0.message.content') ?? 'No content generated.';
        }

        // Fallback: Gemini
        $geminiKey = config('services.gemini.key');
        $geminiModels = ['gemini-2.5-flash', 'gemini-2.0-flash'];
        foreach ($geminiModels as $model) {
            $res = Http::timeout(30)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$geminiKey}",
                ['contents' => [['parts' => [['text' => $prompt]]]]]
            );
            if ($res->successful()) {
                return $res->json('candidates.0.content.parts.0.text') ?? 'No content generated.';
            }
        }

        throw new \Exception('All AI services unavailable. Please try again in a moment.');
    }
}