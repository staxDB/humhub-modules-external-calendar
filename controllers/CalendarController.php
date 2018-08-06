<?php

namespace humhub\modules\external_calendar\controllers;

use Yii;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use humhub\modules\space\models\Space;
use humhub\modules\external_calendar\SyncUtils;
use humhub\modules\external_calendar\permissions\ManageCalendar;
use humhub\modules\content\components\ContentContainerController;
use humhub\modules\external_calendar\models\ExternalCalendar;
use humhub\widgets\ModalClose;

/**
 * CalendarController implements the CRUD actions for all external calendars
 *
 * @author David Born ([staxDB](https://github.com/staxDB))
 */
class CalendarController extends ContentContainerController
{

    /**
     * @inheritdoc
     */
    public $hideSidebar = true;


    public function beforeAction($action)
    {
        if (parent::beforeAction($action)) {
            if ($this->contentContainer instanceof Space && !$this->contentContainer->isMember()) {
                throw new HttpException(403, Yii::t('ExternalCalendarModule.permissions', 'You need to be member of the space "%space_name%" to access this calendar!', ['%space_name%' => $this->contentContainer->name]));
            }
            return true;
        }

        return false;
    }

    /**
     * Lists all ExternalCalendar models.
     * @return mixed
     * @throws HttpException
     * @throws \yii\base\Exception
     */
    public function actionIndex()
    {
        if (!$this->canManageCalendar()) {
            throw new HttpException(403, Yii::t('ExternalCalendarModule.permissions', 'You are not allowed to manage External Calendar!'));
        }

        $models = ExternalCalendar::find()->contentContainer($this->contentContainer)->all();

        return $this->render('index', [
            'models' => $models,
            'contentContainer' => $this->contentContainer,
        ]);
    }

    /**
     * Displays a single ExternalCalendar model.
     * @param integer $id
     * @return mixed
     * @throws HttpException
     * @throws \yii\base\Exception
     */
    public function actionView($id)
    {

        if (!$this->canManageCalendar()) {
            throw new HttpException(403, Yii::t('ExternalCalendarModule.permissions', 'You are not allowed to manage External Calendar!'));
        }

        $model = $this->findModel($id);

        if ($model !== null) {
            return $this->render('view', [
                'model' => $model,
                'contentContainer' => $this->contentContainer,
            ]);
        } else {
            return $this->redirect($this->contentContainer->createUrl('create', array('id' => $id)));
        }
    }

    /**
     * Ajax-method called via button to sync external calendars.
     * @param integer $id
     * @return mixed
     * @throws \yii\base\Exception
     * @throws \Exception
     */
    public function actionSync($id)
    {
        set_time_limit(180); // Set max execution time 3 minutes.
        $calendarModel = ExternalCalendar::find()->contentContainer($this->contentContainer)->where(['external_calendar.id' => $id])->one();

        if ($calendarModel) {
            $ical = SyncUtils::createICal($calendarModel->url);
            if ($ical) {
                // add info to CalendarModel
                $calendarModel->addAttributes($ical);
                $calendarModel->save();

                // check events
                if ($ical->hasEvents()) {
                    $events = SyncUtils::getEvents($calendarModel, $ical);
                    $result = SyncUtils::checkAndSubmitModels($events, $calendarModel);
                    if (!$result) {
                        return ModalClose::widget(['error' => Yii::t('ExternalCalendarModule.sync_result', 'Error while check and submit models...')]);
                    } else {
                        return ModalClose::widget(['success' => Yii::t('ExternalCalendarModule.sync_result', 'Sync successfull!')]);
                    }
                }
            } else {
                return ModalClose::widget(['error' => Yii::t('ExternalCalendarModule.sync_result', 'Error while creating ical... Check if link is reachable.')]);
            }
        } else {
            return ModalClose::widget(['error' => Yii::t('ExternalCalendarModule.sync_result', 'Calendar not found!')]);
        }
        return ModalClose::widget(['warn' => Yii::t('ExternalCalendarModule.sync_result', 'Warning! Something strange happened. Please try again.')]);
    }

    /**
     * Creates a new ExternalCalendar model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     * @throws HttpException
     * @throws \yii\base\Exception
     */
    public function actionCreate()
    {
        if (!$this->canManageCalendar()) {
            throw new HttpException(403, Yii::t('ExternalCalendarModule.permissions', 'You are not allowed to manage External Calendar!'));
        }

        $model = new ExternalCalendar();

        $model->content->setContainer($this->contentContainer);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $ical = SyncUtils::createICal($model->url);
            if ($ical) {
                // add info to CalendarModel
                $model->addAttributes($ical);
                $model->save();
            } else {
                $this->view->error(Yii::t('ExternalCalendarModule.results', 'Error while creating iCal File. Please check, if Url is correct and Internet connection of server is enabled.'));
                return $this->render('create', [
                    'model' => $model,
                    'contentContainer' => $this->contentContainer
                ]);
            }
            $model->changeVisibility();
            $model->save();
            $this->view->success(Yii::t('ExternalCalendarModule.results', 'Calendar successfully created!'));
            return $this->redirect($this->contentContainer->createUrl('view', array('id' => $model->id)));
        } else {
            return $this->render('create', [
                'model' => $model,
                'contentContainer' => $this->contentContainer
            ]);
        }
    }


    /**
     * Updates an existing ExternalCalendar model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws HttpException
     * @throws \yii\base\Exception
     */
    public function actionUpdate($id)
    {
        if (!$this->canManageCalendar()) {
            throw new HttpException(403, Yii::t('ExternalCalendarModule.permissions', 'You are not allowed to manage External Calendar!'));
        }

        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $ical = SyncUtils::createICal($model->url);
            if ($ical) {
                // add info to CalendarModel
                $model->addAttributes($ical);
                $model->save();
            } else {
                $this->view->error(Yii::t('ExternalCalendarModule.results', 'Error while creating iCal File. Please check, if Url is correct and Internet connection of server is enabled.'));
                return $this->render('update', [
                    'model' => $model,
                    'contentContainer' => $this->contentContainer
                ]);
            }
            $this->view->success(Yii::t('ExternalCalendarModule.results', 'Calendar successfully updated!'));
            return $this->redirect($this->contentContainer->createUrl('view', array('id' => $model->id)));
        } else {
            return $this->render('update', [
                'model' => $model,
                'contentContainer' => $this->contentContainer
            ]);
        }
    }


    /**
     * Deletes an existing ExternalCalendar model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws HttpException
     * @throws \Exception
     * @throws \yii\base\Exception
     * @throws \yii\db\StaleObjectException
     */
    public function actionDelete($id)
    {
        if (!$this->canManageCalendar()) {
            throw new HttpException(403, Yii::t('ExternalCalendarModule.base', 'You are not allowed to show External Calendar!'));
        }

        $this->findModel($id)->delete();

        $this->view->success(Yii::t('ExternalCalendarModule.results', 'Calendar successfully deleted!'));
        return $this->redirect($this->contentContainer->createUrl('index'));
    }


    /**
     * Finds the ExternalCalendar model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return ExternalCalendar the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     * @throws \yii\base\Exception
     */
    protected function findModel($id)
    {
        $model = ExternalCalendar::find()->contentContainer($this->contentContainer)->where(['external_calendar.id' => $id])->one();
        if ($model !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * Checks the ManageCalendar permission for the given user on the given contentContainer.
     *
     * Todo: After 1.2.1 use $model->content->canEdit();
     *
     * @return bool
     */
    private function canManageCalendar()
    {
        return $this->contentContainer->permissionManager->can(ManageCalendar::class);
    }
}
