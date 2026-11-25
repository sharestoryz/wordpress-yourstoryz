<?php

declare(strict_types=1);

namespace YourStoryz;

use YourStoryz\Admin\Menu;
use YourStoryz\Admin\PreviewPage;
use YourStoryz\Admin\SettingsPage;
use YourStoryz\Api\GatewayProvider;
use YourStoryz\Frontend\Assets;
use YourStoryz\Frontend\BlockTemplates;
use YourStoryz\Frontend\StoriesBlock;
use YourStoryz\Frontend\StoryBlock;
use YourStoryz\Frontend\TemplateLoader;
use YourStoryz\Frontend\VideoBlock;
use YourStoryz\Import\DeletedStories;
use YourStoryz\Import\ImportRunner;
use YourStoryz\Upgrade\Upgrader;

final class Plugin
{
    public function boot(): void
    {
        add_action('plugins_loaded', [$this, 'register']);
    }

    public function register(): void
    {
        $settings = new Settings;
        $gateways = new GatewayProvider($settings);
        $source = new SourceSettings($gateways);
        $settingsPage = new SettingsPage($settings, $source, $gateways);
        $catalog = new StoryCatalog($gateways);
        $runner = new ImportRunner($settings, $source, $catalog);
        $previewPage = new PreviewPage($source, $catalog, $runner);

        (new Upgrader($settings, $source))->register();
        (new PostType)->register();
        (new DeletedStories)->register();
        (new Assets)->register();
        (new TemplateLoader)->register();
        (new VideoBlock)->register();
        (new StoriesBlock)->register();
        (new StoryBlock)->register();
        (new BlockTemplates)->register();
        $settingsPage->register();
        $previewPage->register();
        add_action(ImportRunner::HOOK, [$runner, 'run']);
        (new Menu($settingsPage, $previewPage))->register();
    }
}
