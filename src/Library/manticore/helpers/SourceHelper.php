<?php 

declare(strict_types=1);

namespace src\Library\manticore\helpers;

use src\Library\manticore\models\Paragraph;

class SourceHelper
{
    public static function fullName(Paragraph $paragraph): string
    {
        $name = "";
        if ($paragraph->genre) {
            $name .= $paragraph->genre.". ";
        }       
        if ($paragraph->author && $paragraph->title) {
            $name .= $paragraph->author ." — " . $paragraph->title;
        } 
        if ($paragraph->author && !$paragraph->title) {
            $name .= $paragraph->author;
        }
        if ($paragraph->title && !$paragraph->author) {
            $name .= $paragraph->title;
        }
        return $name;
    }
}