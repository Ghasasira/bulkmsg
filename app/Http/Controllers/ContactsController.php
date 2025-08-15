<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactsController extends Controller
{
    public function index(Request $request): Response
    {
        $users = Customer::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('number1', 'like', "%{$search}%")
                    ->orWhere('number2', 'like', "%{$search}%");
            })
            ->latest()
            ->get()
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'number1' => $user->number1,
                'local_amt' => $user->local_amt,
                'no_due_days' => $user->no_due_days,
                'number2' => $user->number2,
                'created_at' => $user->created_at->toISOString(),
            ]);
        // dd($users);
        return Inertia::render('customers/index', [
            'customers' => $users,
            'filters' => [
                'search' => $request->search,
            ]
        ]);
    }

    // public function index(Request $request): Response
    // {
    //     $users = Contact::query()
    //         ->when($request->search, function ($query, $search) {
    //             $query->where('name', 'like', "%{$search}%")
    //                 ->orWhere('phone', 'like', "%{$search}%")
    //                 ->orWhere('email', 'like', "%{$search}%");
    //         })
    //         ->latest()
    //         ->get()
    //         ->map(fn($user) => [
    //             'id' => $user->id,
    //             'name' => $user->name,
    //             'phone' => $user->phone,
    //             'email' => $user->email,
    //             'created_at' => $user->created_at->toISOString(),
    //         ]);

    //     return Inertia::render('contacts/index', [
    //         'users' => $users,
    //         'filters' => [
    //             'search' => $request->search,
    //         ]
    //     ]);
    // }

    public function create(): Response
    {
        return Inertia::render('contacts/create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone',
            'email' => 'nullable|email|unique:users,email',
        ]);

        Contact::create($validated);

        return redirect()->back()->with('success', 'User created successfully');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone,' . $user->id,
            'email' => 'nullable|email|unique:users,email,' . $user->id,
        ]);

        $user->update($validated);

        return redirect()->back()->with('success', 'User updated successfully');
    }

    public function destroy(Contact $user)
    {
        $user->delete();

        return redirect()->back()->with('success', 'User deleted successfully');
    }
}
