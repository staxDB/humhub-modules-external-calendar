<?php

namespace humhub\modules\external_calendar\controllers;

use humhub\modules\external_calendar\models\ICalSync;
use Yii;
use yii\base\InvalidValueException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use humhub\components\access\ControllerAccess;
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

    public function getAccessRules()
    {
        return [[ControllerAccess::RULE_PERMISSION => [ManageCalendar::class]]];
    }

    /**
     * Lists all ExternalCalendar models.
     * @return mixed
     * @throws \yii\base\Exception
     */
    public function actionIndex()
    {
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
        $model = $this->findModel($id);

        return $this->render('view', [
            'model' => $model,
            'contentContainer' => $this->contentContainer,
        ]);
    }

    /**
     * Ajax-method called via button to sync external calendars.
     * @param integer $id
     * @return mixed
     * @throws \Exception
     */
    public function actionSync($id)
    {
        set_time_limit(180); // Set max execution time 3 minutes.

        try {
            $calendarModel = $this->findModel($id);
            $calendarModel->sync();
            return ModalClose::widget(['success' => Yii::t('ExternalCalendarModule.sync_result', 'Sync successfull!')]);
        } catch (InvalidValueException $e) {
            Yii::error($e);
            return ModalClose::widget(['error' => Yii::t('ExternalCalendarModule.sync_result', $e->getMessage())]);
        } catch (NotFoundHttpException $e) {
            Yii::error($e);
            return ModalClose::widget(['error' => Yii::t('ExternalCalendarModule.sync_result', 'Calendar not found!')]);
        } catch (\Exception $e) {
            Yii::error($e);
            return ModalClose::widget(['error' => Yii::t('ExternalCalendarModule.sync_result', 'An unknown error occured while synchronizing your calendar!')]);
        }
    }

    /**
     * @param null $id
     * @return CalendarController|string|\yii\console\Response|\yii\web\Response
     * @throws NotFoundHttpException
     * @throws \yii\base\Exception
     * @throws \Throwable
     */
    public function actionEdit($id = null)
    {
        $model = ($id) ? $this->findModel($id) : new ExternalCalendar($this->contentContainer);

        try {
            if ($model->load(Yii::$app->request->post())) {
                (new ICalSync(['calendarModel' => $model, 'skipEvents' => true]))->syncICal();
                $this->view->success(Yii::t('ExternalCalendarModule.results', 'Calendar successfully created!'));
                return $this->redirect($this->contentContainer->createUrl('view', ['id' => $model->id]));
            } else {
                return $this->render('edit', [
                    'model' => $model,
                    'contentContainer' => $this->contentContainer
                ]);
            }
        } catch (\Exception $e) {
            Yii::warning($e);
            $this->view->error(Yii::t('ExternalCalendarModule.results', 'Error while creating iCal File. Please check, if Url is correct and Internet connection of server is enabled.'));
            return $this->render('edit', [
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
     * @throws \Throwable
     */
    public function actionDelete($id)
    {
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

        if (!$model) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        return $model;
    }
}
