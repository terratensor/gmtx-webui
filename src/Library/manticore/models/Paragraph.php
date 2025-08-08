<?php

declare(strict_types=1);

namespace src\Library\manticore\models;

use yii\base\Model;

class Paragraph extends Model
{
    public string $genre;
    public string $genre_attr;
    public string $author;
    public string $author_attr;
    public string $title;
    public string $title_attr;
    public string $content;
    public int $chunk;
    public int $char_count;
    public int $word_count;
    public string $language;
    public float $ocr_quality;
    public array $highlight;
    public string $source_uuid;
    public string $source;
    public int $datetime;
    public int $created_at;
    public int $updated_at;
    public int $id = 0;


   public static function build(array $hitData): self
    {
        $model = new static();
        
        $attributes = [];
        foreach ($hitData as $key => $value) {
            $newKey = str_replace('library2025.', '', $key);
            $attributes[$newKey] = $value;
        }
        
        // Присваиваем атрибуты модели
        $model->setAttributes($attributes, false);
        
        return $model;
    }

    public static function create(
        string $text,
        string $position,
        string $length,
        array $highlight,
    ): self {
        $paragraph = new static();

        $paragraph->content = $text;
        $paragraph->chunk = (int)$position;
        $paragraph->char_count = (int)$length;
        $paragraph->highlight = $highlight;

        return $paragraph;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }
}
