<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $category = $request->query('category');

        $feedback = Feedback::query()
            ->with('user')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('subject', 'like', "%{$q}%")
                        ->orWhere('message', 'like', "%{$q}%");
                });
            })
            ->when(
                in_array($category, ['criticism', 'suggestion', 'bug', 'other'], true),
                fn ($query) => $query->where('category', $category)
            )
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.feedback.index', compact('feedback', 'q', 'category'));
    }

    public function show(Feedback $feedback): View
    {
        $feedback->load('user');

        return view('admin.feedback.show', compact('feedback'));
    }

    public function destroy(Feedback $feedback): RedirectResponse
    {
        $feedback->delete();

        return redirect()
            ->route('admin.feedback.index')
            ->with('success', 'Feedback berhasil dihapus.');
    }
}
