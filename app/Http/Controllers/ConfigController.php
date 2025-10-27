<?php
// app/Http/Controllers/ConfigController.php
namespace App\Http\Controllers;

use App\Models\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ConfigController extends Controller
{
    public function show()
    {
        $cfg = Cache::remember('app_config_full', 300, fn () => Config::first());
        return response()->json($cfg);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'price_course' => 'nullable|numeric|min:0',
            'price_session'=> 'nullable|numeric|min:0',
            'price_booking'=> 'nullable|numeric|min:0',
            'stripe_default_region' => 'required|in:es,us',
        ]);

        $cfg = Config::firstOrCreate(['singleton' => 'X']);
        $cfg->fill($validated)->save();

        Cache::forget('app_config_full');
        return response()->json($cfg);
    }
}



