<?php

declare(strict_types=1);

namespace frontend\controllers;

use Yii;
use Exception;
use yii\web\Response;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use src\Search\forms\SearchForm;
use yii\web\NotFoundHttpException;
use src\Library\manticore\services\TitleService;
use src\Library\manticore\services\AuthorService;
use src\Library\manticore\services\ContextService;
use src\Library\manticore\services\ManticoreService;
use src\Search\Http\Action\V1\SearchSettings\ToggleAction;
use src\Library\manticore\services\EmptySearchRequestExceptions;

class LibraryController extends Controller
{
    private ManticoreService $service;
    private ContextService $contextService;
    private AuthorService $authorService;
    private TitleService $titleService;

    public function __construct(
        $id,
        $module,
        ManticoreService $service,
        ContextService $contextService,
        AuthorService $authorService,
        TitleService $titleService,
        $config = []
    ) {
        parent::__construct($id, $module, $config);
        $this->service = $service;
        $this->contextService = $contextService;
        $this->authorService = $authorService;
        $this->titleService = $titleService;
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

        $form->load(Yii::$app->request->queryParams);
        // Сброс paragraphId при новом текстовом запросе
        if ($form->query && $form->paragraphId) {
            $form->paragraphId = null;
        }
        // Обработка запроса по ID параграфа
        if ($form->paragraphId && $form->matching === 'vector') {
            $form->query = ''; // Очищаем текстовый запрос
        }
        if ($form->isEmpty()) {
            return $this->redirect('index');
        }

        $similarity_model = Yii::$app->request->queryParams['model'] ?? null;
        
        try {
            if ($form->load(Yii::$app->request->queryParams) && $form->validate()) {
                if ($form->isEmpty()) {
                    return $this->redirect('index');
                }
                $results = $this->service->search($form, $similarity_model);
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
            'similarity_model' => $similarity_model
        ]);
    }

    public function actionParagraph($id)
    {
        try {
            $quoteResult = $this->contextService->handle($id);
            // $model = $this->service->findParagraphById($id);
        } catch (\DomainException $e) {
            throw new NotFoundHttpException('Запрашиваемый параграф не найден');
        };
        return $this->render('paragraph', [
            'quoteResult' => $quoteResult,
        ]);
    }

    public function actionAuthor($q)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $result = $this->authorService->findAuthor($q);
        return $result;
    }

    public function actionTitle($q)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $result = $this->titleService->findTitle($q);
        return $result;
    }
}
