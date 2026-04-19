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
                $q->where('product_name', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        return response()->json($query->paginate(10));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_name'    => 'required|string|max:255',
            'description'     => 'required|string',
            'features'        => 'required|string',
            'target_audience' => 'required|string|max:255',
            'price'           => 'required|string|max:255',
            'usp'             => 'nullable|string|max:500',
            'style_template'  => 'required|string|in:modern,minimal,bold',
        ]);

        $prompt = $this->buildPrompt($request->all());
        $rawResponse = $this->callAI($prompt);
        $generatedJson = $this->parseJson($rawResponse);

        $generation = $request->user()->contentGenerations()->create([
            'product_name'    => $request->product_name,
            'description'     => $request->description,
            'features'        => $request->features,
            'target_audience' => $request->target_audience,
            'price'           => $request->price,
            'usp'             => $request->usp,
            'generated_json'  => json_encode($generatedJson),
            'style_template'  => $request->style_template,
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
        $styleHint = match ($data['style_template']) {
            'minimal' => 'clean, whitespace-heavy, simple language',
            'bold'    => 'high-impact, aggressive, bold power words, urgency-driven',
            default   => 'modern, professional, benefit-focused, conversational',
        };

        $prompt  = "You are an expert conversion copywriter. Generate sales page content for the product below.\n\n";
        $prompt .= "PRODUCT:\n";
        $prompt .= "- Name: {$data['product_name']}\n";
        $prompt .= "- Description: {$data['description']}\n";
        $prompt .= "- Features: {$data['features']}\n";
        $prompt .= "- Target Audience: {$data['target_audience']}\n";
        $prompt .= "- Price: {$data['price']}\n";

        if (!empty($data['usp'])) {
            $prompt .= "- USP: {$data['usp']}\n";
        }

        $prompt .= "\nCOPYWRITING STYLE: {$styleHint}\n\n";
        $prompt .= "Return ONLY a valid JSON object - no markdown, no ```json, no explanation. Start directly with {.\n\n";
        $prompt .= "Required JSON structure (follow exactly):\n";
        $prompt .= <<<'EOT'
{
  "headline": "main hero headline (powerful, attention-grabbing, max 10 words)",
  "subheadline": "supporting headline that expands on the main promise (1-2 sentences)",
  "hook": "short social proof or credibility line (e.g. 'Trusted by 10,000+ teams')",
  "overview": "2-3 paragraph product overview, persuasive and benefit-focused",
  "benefits": [
    { "icon": "emoji", "title": "benefit title (3-5 words)", "description": "1-2 sentence explanation" }
  ],
  "features": [
    { "name": "feature name", "description": "short feature description" }
  ],
  "testimonials": [
    { "quote": "realistic testimonial quote", "name": "Full Name", "role": "Job Title", "company": "Company Name" }
  ],
  "pricing": {
    "price": "price string",
    "period": "billing period or null",
    "includes": ["what is included item 1", "item 2"]
  },
  "cta_primary": "primary CTA button text (action verb, e.g. 'Get Instant Access')",
  "cta_secondary": "secondary CTA text or null",
  "urgency": "urgency/scarcity line or null"
}
EOT;
        $prompt .= "\n\nRules: generate 3-5 benefits, all listed features as feature objects, exactly 3 testimonials, at least 4 pricing includes items. Write copy specifically for this product and audience.";

        return $prompt;
    }

    private function parseJson(string $raw): array
    {
        $clean = preg_replace('/```(?:json)?\s*([\s\S]*?)```/i', '$1', trim($raw));
        $clean = trim($clean);

        $data = json_decode($clean, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            if (preg_match('/\{[\s\S]*\}/u', $clean, $matches)) {
                $data = json_decode($matches[0], true);
            }
        }

        if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('AI returned invalid content. Please try again.');
        }

        return $data;
    }

    private function callAI(string $prompt): string
    {
        // Primary: Groq
        $groqKey = config('services.groq.key');
        $response = Http::timeout(60)->withHeaders([
            'Authorization' => "Bearer {$groqKey}",
        ])->post('https://api.groq.com/openai/v1/chat/completions', [
            'model'       => 'llama-3.3-70b-versatile',
            'messages'    => [['role' => 'user', 'content' => $prompt]],
            'max_tokens'  => 2048,
            'temperature' => 0.7,
        ]);

        if ($response->successful()) {
            return $response->json('choices.0.message.content') ?? '';
        }

        // Fallback: Gemini
        $geminiKey = config('services.gemini.key');
        foreach (['gemini-2.5-flash', 'gemini-2.0-flash'] as $model) {
            $res = Http::timeout(60)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$geminiKey}",
                ['contents' => [['parts' => [['text' => $prompt]]]]]
            );
            if ($res->successful()) {
                return $res->json('candidates.0.content.parts.0.text') ?? '';
            }
        }

        throw new \Exception('All AI services unavailable. Please try again in a moment.');
    }
}
