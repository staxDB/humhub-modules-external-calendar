<?php

namespace humhub\modules\external_calendar\controllers;

use Yii;
use yii\web\HttpException;
use humhub\widgets\ModalClose;
use humhub\modules\content\components\ContentContainerController;
use humhub\modules\external_calendar\permissions\ManageEntry;
use humhub\modules\external_calendar\models\ExternalCalendarEntry;

/**
 * EntryController implements the CRUD actions for all external calendar entries
 *
 * @author davidborn
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
     * @throws \Exception
     */
    public function actionUpdate($id, $cal = null)
    {
        $model = $this->getCalendarEntry($id);

        if (!$model->content->canEdit()) {
            throw new HttpException(403);
        }

        if (!$model) {
            throw new HttpException('404');
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            if (empty($cal)) {
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
     * @throws \yii\base\Exception
     */
    protected function getCalendarEntry($id)
    {
        return ExternalCalendarEntry::find()->contentContainer($this->contentContainer)->readable()->where(['external_calendar_entry.id' => $id])->one();
    }
}
