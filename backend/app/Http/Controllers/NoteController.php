<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notes = $request->user()->notes()->latest()->get();

        return response()->json(['data' => $notes], 200);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
        ]);

        $note = $request->user()->notes()->create($data);

        return response()->json(['message' => 'Catatan dibuat', 'data' => $note], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $note = $request->user()->notes()->findOrFail($id);

        return response()->json(['data' => $note], 200);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $note = $request->user()->notes()->findOrFail($id);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
        ]);

        $note->update($data);

        return response()->json(['message' => 'Catatan diperbarui', 'data' => $note], 200);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $request->user()->notes()->findOrFail($id)->delete();

        return response()->json(['message' => 'Catatan dihapus'], 200);
    }
}
