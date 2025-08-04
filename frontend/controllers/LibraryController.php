<?php

declare(strict_types=1);

namespace frontend\controllers;

use Yii;
use Exception;
use src\Library\manticore\services\AuthorService;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use src\Search\forms\SearchForm;
use src\Library\manticore\services\ContextService;
use src\Library\manticore\services\ManticoreService;
use src\Search\Http\Action\V1\SearchSettings\ToggleAction;
use src\Library\manticore\services\EmptySearchRequestExceptions;
use yii\web\Response;

class LibraryController extends Controller
{
    private ManticoreService $service;
    private ContextService $contextService;

    private AuthorService $authorService;

    public function __construct(
        $id,
        $module,
        ManticoreService $service,
        ContextService $contextService,
        AuthorService $authorService,
        $config = []
    ) {
        parent::__construct($id, $module, $config);
        $this->service = $service;
        $this->contextService = $contextService;
        $this->authorService = $authorService;
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['search', 'context'], // Указываем, к каким действиям применяем контроль
                'rules' => [
                    [
                        'actions' => ['search', 'context'],
                        'allow' => true,
                        'roles' => ['member'], // Только пользователи с ролью member
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => \yii\web\ErrorAction::class,
            ],
            'captcha' => [
                'class' => \yii\captcha\CaptchaAction::class,
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
            'search-settings' => [
                'class' => ToggleAction::class,
            ],
        ];
    }
    public function actionIndex(): string
    {
        $results = null;
        $form = new SearchForm();
        $errorQueryMessage = '';

        $results = $this->service->facets();
        // $results_json = json_encode($results, JSON_UNESCAPED_UNICODE);
        return $this->render('index', [
            'results' => $results,
            'model' => $form,
            'errorQueryMessage' => $errorQueryMessage
        ]);
    }

    public function actionSearch(): string|Response
    {
        $results = null;
        $form = new SearchForm();
        $errorQueryMessage = '';

        try {
            if ($form->load(Yii::$app->request->queryParams) && $form->validate()) {
                if ($form->isEmpty()) {
                    return $this->redirect('index');
                }
                $results = $this->service->search($form);
            }
        } catch (\DomainException $e) {
            Yii::$app->errorHandler->logException($e);
            Yii::$app->session->setFlash('error', $e->getMessage());
        } catch (EmptySearchRequestExceptions $e) {
            $errorQueryMessage = $e->getMessage();
        } catch (Exception $e) {
            $errorQueryMessage = $e->getMessage();
        }

        return $this->render('search', [
            'results' => $results ?? null,
            'aggs' => $aggs ?? [],
            'model' => $form,
            'errorQueryMessage' => $errorQueryMessage,
        ]);
    }

    public function actionAuthor($q)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $result = $this->authorService->findAuthor($q);

        return $result;
    }
}
