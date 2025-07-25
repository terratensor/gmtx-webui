<?php

declare(strict_types=1);

namespace src\Search\forms;

use yii\base\Model;

class ToggleForm extends Model
{
    public string $value = '';

    public function rules(): array
    {
        return [
            ['value', 'in', 'range' => array_keys($this->getValues())],
        ];
    }

    public function getValues(): array
    {
        return [
            'toggle' => 'toggle',
        ];
    }
}
