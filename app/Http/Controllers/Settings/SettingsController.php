<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class SettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        abort(404);
    }

    public function editMenuSettings(Request $request)
    {
        $type = 'Menu';
        $this->authorize('menu settings');
        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'Settings'],
            ['name' => \Str::title($type).' Settings'],
        ];
        $settingsAll = Setting::whereNull('user_id')->whereIn('key', ['sidebar', 'footer'])->get();

        // Initialize variables with null by default
        $sidebar = null;
        $footer = null;

        // Process the settings and assign them to variables
        foreach ($settingsAll as $setting) {
            $key = $setting->key; // Assuming 'key' is the column name
            $value = $setting->value; // Assuming 'value' is the column name

            // Decode JSON if applicable, or assign null
            $$key = is_string($value) && json_decode($value) !== null ? json_decode($value, true) : $value;
        }
        //        dd($sidebar, $footer);

        return view()->make('content.settings.edit')
            ->with([
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
                'title' => \Str::title($type).' Settings',
                'type' => $type,
                'settings' => ['menu' => [
                    'sidebar' => $sidebar,
                    'footer' => $footer,
                ],
                ],
            ]);
    }

    public function editSiteSettings(Request $request)
    {
        $type = 'Site';

        $this->authorize('site settings');

        $pageConfigs = ['layoutWidth' => 'full'];

        $breadcrumbs = [
            ['name' => 'Settings'],
            ['name' => \Str::title($type).' Settings'],
        ];

        return view()->make('content.settings.edit')
            ->with([
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
                'title' => \Str::title($type).' Settings',
                'type' => $type,
                'settings' => Setting::whereNull('user_id')->get()?->pluck('value', 'key'),
            ]);
    }

    public function editFeaturedImageSettings(Request $request)
    {
        $type = 'featured-images';

        $this->authorize('featured images');

        $pageConfigs = ['layoutWidth' => 'full'];

        $title = \Str::title(str_replace(['-', '_'], ' ', $type));

        $breadcrumbs = [
            ['name' => 'Settings'],
            ['name' => $title],
        ];

        $settings = Setting::whereNull('user_id')->get()?->pluck('value', 'key');
        $settings = $settings->map(function ($item, $key) {
            if (is_string($item) && json_decode($item) !== null) {
                return json_decode($item, true);
            }

            return $item;
        });

        return view()->make('content.settings.edit')
            ->with([
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
                'title' => $title,
                'type' => $type,
                'settings' => $settings,
            ]);
    }

    public function update(Request $request, $type)
    {
        $this->authorize('update settings');

        $theType = strtolower($type);

        switch ($theType) {
            case 'site':
                $this->updateSiteSettings($request);

                break;
            case 'menu':
                $this->updateMenuSettings($request);

                break;
            case 'featured-images':
                $this->updateFeaturedImagesSettings($request);

                break;
        }

        Cache::forget('settings.site.all'); // Clear site settings cache
        if (!empty($request->user_id)) {
            Cache::forget('user_settings_'.$request->user_id); // Clear user settings cache
        }

        return redirect()->route('settings.'.$theType.'.edit')
            ->with('success', 'Settings saved successfully!');
    }

    private function updateUserSettings(Request $request)
    {
        $userId = $request->input('user_id');
        $key = $request->input('key');
        $value = $request->input('value');
        Setting::updateOrCreate(
            ['user_id' => $userId, 'key' => $key],
            ['value' => $value]
        );
    }

    private function updateSiteSettings(Request $request): void
    {
        foreach ($request->all() as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['key' => $key, 'value' => $value]);
            Config::set('settings.site.'.$key, $value);
        }
    }

    private function updateMenuSettings(Request $request): void
    {
        $menu = $request->menu;

        foreach ($menu as $key => $menuItem) {
            // Process each menu item
            $processedMenu = array_map(function ($item) {
                // Update the "target" field if it exists and is not empty
                if (isset($item['target']) && !empty($item['target'])) {
                    $item['target'] = $item['target'][0];
                }

                return $item;
            }, $menuItem);

            // Filter out items where both "title" and "link" are empty
            $processedMenu = array_filter($processedMenu, function ($item) {
                return !(empty($item['title']) && empty($item['link']));
            });

            // Save the processed menu to the database and config
            Setting::updateOrCreate(['key' => $key], ['key' => $key, 'value' => json_encode($processedMenu)]);
            Config::set('settings.menu.'.$key, json_encode($processedMenu));
        }
    }

    private function updateFeaturedImagesSettings(Request $request): void
    {
        $featuredImages = $request->input('featured_images', []);
        // Each item should be an array with 'image' and 'link'
        $processedImages = array_filter($featuredImages, function ($item) {
            return !empty($item['image']) && !empty($item['link']);
        });

        Setting::updateOrCreate(
            ['key' => 'featured_images'],
            ['key' => 'featured_images', 'value' => json_encode($processedImages)]
        );
        Config::set('settings.featured_images', json_encode($processedImages));
    }
}
