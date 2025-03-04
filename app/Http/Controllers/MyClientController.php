<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MyClient;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class MyClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clients = MyClient::all();
        return response()->json($clients);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:250',
            'slug' => 'required|string|max:100|unique:my_client,slug',
            'client_logo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasFile('client_logo')) {
            $validated['client_logo'] = $request->file('client_logo')->store('clients', 's3');
        }

        $client = MyClient::create($validated);
        Redis::set("client:{$client->slug}", json_encode($client));

        return response()->json($client, 201);
    }

    public function show($slug)
    {
        if ($client = Redis::get("client:{$slug}")) {
            return response()->json(json_decode($client));
        }

        $client = MyClient::where('slug', $slug)->firstOrFail();
        Redis::set("client:{$slug}", json_encode($client));

        return response()->json($client);
    }

    public function update(Request $request, $slug)
    {
        $client = MyClient::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:250',
            'client_logo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasFile('client_logo')) {
            Storage::disk('s3')->delete($client->client_logo);
            $validated['client_logo'] = $request->file('client_logo')->store('clients', 's3');
        }

        $client->update($validated);

        Redis::del("client:{$slug}");
        Redis::set("client:{$slug}", json_encode($client));

        return response()->json($client);
    }

    public function destroy($slug)
    {
        $client = MyClient::where('slug', $slug)->firstOrFail();
        $client->delete();

        Redis::del("client:{$slug}");

        return response()->json(['message' => 'Client deleted'], 200);
    }
}
