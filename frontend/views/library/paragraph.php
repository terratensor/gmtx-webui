<?php

declare(strict_types=1);

use src\Search\helpers\SearchResultHelper;
use src\Library\manticore\models\Paragraph;
use src\Search\models\ContextPDO;

/** @var yii\web\View $this */
/** @var ContextPDO $quoteResult */

/** @var string $bookName */
$bookName = $quoteResult->bookName;
$paragraph = $quoteResult->paragraph;
$this->title = $bookName;

?>
<div class="container-fluid quote-results">
    <div class="row">
        <div class="col-md-12">
            <div class="card pt-3 my-3">
                <div class="card-body p-0">
                    <div class="px-xl-5 px-lg-5 px-md-5 px-sm-3 paragraph">
                        <div id="<?= $paragraph->chunk; ?>" data-entity-id="<?= $paragraph->id; ?>">
                            <div class="card-body">

                                <div class="paragraph-text">
                                    <?= SearchResultHelper::highlightFieldContent($paragraph, 'content', 'markdown', false); ?>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-start book-name pt-4">
                            <div><strong><i><?= $bookName; ?></i></strong></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>