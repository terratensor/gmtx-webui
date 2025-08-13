<?php

declare(strict_types=1);

namespace src\Library\manticore\ext;

use Manticoresearch\Query\KnnQuery as ManticoreKnnQuery;

class KnnQuery extends ManticoreKnnQuery
{
	protected $knnField;
	protected $knnTargetKey;
	protected $knnTarget;
	protected $docCount;
    protected $ef;
	protected $rescore;
	protected $oversampling;

	public function __construct($knnField, $knnTarget, $docCount, $ef = 100, $rescore = true, $oversampling = 3.0) {
		parent::__construct($knnField, $knnTarget, $docCount);
        $this->ef = $ef;
		$this->rescore = $rescore;
		$this->oversampling = $oversampling;
	}

    public function toArray() {
		$paramArr = [
			'field' => $this->knnField,
			$this->knnTargetKey => $this->knnTarget,
			'k' => $this->docCount,
            'ef' => $this->ef,
			// 'rescore' => $this->rescore,
			// 'oversampling' => $this->oversampling
		];
		if ($this->params) {
			$paramArr['filter'] = ['bool' => $this->params];
		}
		return $this->convertArray($paramArr);
	}
}