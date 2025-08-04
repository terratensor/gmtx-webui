<?php

namespace frontend\controllers;

use Yii;
use Exception;
use yii\web\Controller;
use yii\filters\VerbFilter;
use common\models\LoginForm;
use yii\filters\AccessControl;
use frontend\models\SignupForm;
use frontend\models\ContactForm;
use src\Search\forms\SearchForm;
use yii\web\NotFoundHttpException;
use frontend\models\VerifyEmailForm;
use yii\web\BadRequestHttpException;
use frontend\models\ResetPasswordForm;
use yii\base\InvalidArgumentException;
use frontend\models\PasswordResetRequestForm;
use frontend\models\ResendVerificationEmailForm;
use src\Library\manticore\services\ContextService;
use src\Library\manticore\services\ManticoreService;
use src\Search\Http\Action\V1\SearchSettings\ToggleAction;
use src\Library\manticore\services\EmptySearchRequestExceptions;

/**
 * Site controller
 */
class SiteController extends Controller
{

    private ManticoreService $service;
    private ContextService $contextService;

    public function __construct(
        $id,
        $module,
        ManticoreService $service,
        ContextService $contextService,
        $config = []
    ) {
        parent::__construct($id, $module, $config);
        $this->service = $service;
        $this->contextService = $contextService;
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

    /**
     * Displays homepage.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Displays contact page.
     *
     * @return mixed
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail(Yii::$app->params['adminEmail'])) {
                Yii::$app->session->setFlash('success', 'Thank you for contacting us. We will respond to you as soon as possible.');
            } else {
                Yii::$app->session->setFlash('error', 'There was an error sending your message.');
            }

            return $this->refresh();
        }

        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return mixed
     */
    public function actionAbout()
    {
        return $this->render('about');
    }

    // public function actionSearch(): string
    // {
    //     $results = null;
    //     $form = new SearchForm();
    //     $errorQueryMessage = '';

    //     // $aggs = $this->service->aggs($form);
    //     // var_dump($form->isEmpty());

    //     try {
    //         if ($form->load(Yii::$app->request->queryParams) && $form->validate()) {
    //             if ($form->isEmpty()) {
    //                 // var_dump('empty');
    //                 $results = $this->service->facets();
    //                 return $this->render('empty_search', [
    //                     'results' => $results,
    //                     'model' => $form,
    //                     'errorQueryMessage' => $errorQueryMessage
    //                 ]);
    //             }
    //             $results = $this->service->search($form);
    //         }
    //     } catch (\DomainException $e) {
    //         Yii::$app->errorHandler->logException($e);
    //         Yii::$app->session->setFlash('error', $e->getMessage());
    //     } catch (EmptySearchRequestExceptions $e) {
    //         $errorQueryMessage = $e->getMessage();
    //     } catch (Exception $e) {
    //         $errorQueryMessage = $e->getMessage();
    //     }

    //     return $this->render('search', [
    //         'results' => $results ?? null,
    //         'aggs' => $aggs ?? [],
    //         'model' => $form,
    //         'errorQueryMessage' => $errorQueryMessage,
    //     ]);
    // }

    public function actionContext($id): string
    {
        $this->layout = 'print';
        $errorQueryMessage = 'The requested page does not exist.';

        try {

            $quoteResults = $this->contextService->handle($id);
            $results = $this->service->search($quoteResults->searchForm);

            // var_dump($quoteResults->bookName); die();

            return $this->render('context', [
                'results' => $results,
                'bookName' => $quoteResults->bookName,
            ]);
        } catch (\DomainException $e) {
            Yii::$app->errorHandler->logException($e);
            Yii::$app->session->setFlash('error', $e->getMessage());
        } catch (EmptySearchRequestExceptions $e) {
            $errorQueryMessage = $e->getMessage();
        }

        throw new NotFoundHttpException($errorQueryMessage);
    }
}
