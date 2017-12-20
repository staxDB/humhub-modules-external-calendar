<?php

namespace humhub\modules\external_calendar\controllers;

use Yii;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use humhub\widgets\ModalClose;
use humhub\modules\content\components\ContentContainerController;
use humhub\modules\external_calendar\permissions\ManageEntry;
use humhub\modules\external_calendar\models\ExternalCalendarEntry;



/**
 * EntryController implements the CRUD actions for ExternalCalendarEntry model.
 */
class EntryController extends ContentContainerController
{

    /**
     * @inheritdoc
     */
    public $hideSidebar = true;

    /**
     * Displays a single ExternalCalendarEntry model.
     * @param integer $id
     * @param null $cal
     * @return mixed
     * @throws HttpException
     */
    public function actionView($id, $cal = null)
    {
        $model = $this->getCalendarEntry($id);

        if (!$model) {
            throw new HttpException('404');
        }

        // We need the $cal information, since the update redirect in case of fullcalendar view is other than stream view
        if ($cal) {
            return $this->renderModal($model, $cal);
        }

        return $this->render('view', [
            'model' => $model,
            'stream' => true
        ]);
    }

    private function renderModal($model, $cal)
    {
        return $this->renderAjax('modal', [
            'model' => $model,
            'editUrl' => $this->contentContainer->createUrl('/external_calendar/entry/update', ['id' => $model->id, 'cal' => $cal]),
            'canManageEntries' => $model->content->canEdit() || $this->canManageEntries(),
            'contentContainer' => $this->contentContainer,
        ]);
    }

    /**
     * Updates an existing ExternalCalendarEntry model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @param null $cal
     * @return mixed
     * @throws HttpException
     */
    public function actionUpdate($id, $cal = null)
    {
        $model = $this->getCalendarEntry($id);

        if(!$model->content->canEdit()) {
            throw new HttpException(403);
        }

        if (!$model) {
            throw new HttpException('404');
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            if(empty($cal)) {
                return ModalClose::widget(['saved' => true]);
            } else {
                return $this->renderModal($model, 1);
            }
        }

        return $this->renderAjax('update', [
            'model' => $model,
            'contentContainer' => $this->contentContainer,
            'editUrl' => $this->contentContainer->createUrl('/external_calendar/entry/update', ['id' => $model->id, 'cal' => $cal]),
        ]);
//
//        // We need the $cal information, since the edit redirect in case of fullcalendar view is other than stream view
//        if ($cal) {
//            return $this->renderModal($model, $cal);
//        }



//        $model = $this->findModel($id);
//
//        if ($model->load(Yii::$app->request->post()) && $model->save()) {
//            return $this->redirect(['view', 'id' => $model->id]);
//        } else {
//            return $this->render('update', [
//                'model' => $model,
//            ]);
//        }
    }

    /**
     * Deletes an existing ExternalCalendarEntry model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws HttpException
     */
    public function actionDelete($id)
    {
        $calendarEntry = $this->getCalendarEntry(Yii::$app->request->get('id'));

        if ($calendarEntry == null) {
            throw new HttpException('404', Yii::t('ExternalCalendarModule.base', "Event not found!"));
        }

        if (!($this->canManageEntries() ||  $calendarEntry->content->canEdit())) {
            throw new HttpException('403', Yii::t('ExternalCalendarModule.base', "You don't have permission to delete this event!"));
        }

        if (Yii::$app->request->isAjax) {
            $this->asJson(['success' => true]);
        } else {
            return $this->redirect($this->contentContainer->createUrl('/external_calendar/view/index'));
        }
//
//        $calendarEntry->delete();
//
//        $this->findModel($id)->delete();
//
//        return $this->redirect(['index']);
    }

    /**
     * Finds the ExternalCalendarEntry model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return ExternalCalendarEntry the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = ExternalCalendarEntry::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * Checks the ManageEntry permission for the given user on the given contentContainer.
     *
     * Todo: After 1.2.1 use $entry->content->canEdit();
     *
     * @return bool
     */
    private function canManageEntries()
    {
        return $this->contentContainer->permissionManager->can(new ManageEntry);
    }

    /**
     * Returns a readable calendar entry by given id
     *
     * @param int $id
     * @return ExternalCalendarEntry
     */
    protected function getCalendarEntry($id)
    {
        return ExternalCalendarEntry::find()->contentContainer($this->contentContainer)->readable()->where(['external_calendar_entry.id' => $id])->one();
    }
}
