<?php

declare(strict_types=1);

namespace YourStoryz\Frontend;

use YourStoryz\PostType;

final class TemplateLoader
{
    private const SINGLE = 'single-story.php';

    private const ARCHIVE = 'archive-story.php';

    private const THEME_DIRECTORY = 'yourstoryz/';

    public function __construct(
        private readonly string $directory = YOURSTORYZ_DIR,
    ) {}

    public function register(): void
    {
        add_filter('template_include', [$this, 'filter']);
    }

    public function filter(string $template): string
    {
        if (wp_is_block_theme()) {
            return $template;
        }

        if (is_singular(PostType::NAME)) {
            return $this->resolve(self::SINGLE);
        }

        if (is_post_type_archive(PostType::NAME)) {
            return $this->resolve(self::ARCHIVE);
        }

        return $template;
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function part(string $name, array $args = []): void
    {
        load_template($this->resolve('parts/'.$name.'.php'), false, $args);
    }

    private function resolve(string $file): string
    {
        $themeTemplate = locate_template([self::THEME_DIRECTORY.$file]);

        if ($themeTemplate !== '') {
            return $themeTemplate;
        }

        return $this->directory.'templates/'.$file;
    }
}
