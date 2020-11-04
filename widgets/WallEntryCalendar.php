<?php

namespace humhub\modules\external_calendar\widgets;

use humhub\modules\content\widgets\stream\WallStreamModuleEntryWidget;
use humhub\modules\external_calendar\assets\Assets;
use humhub\modules\external_calendar\models\ExternalCalendar;

/**
 * @inheritdoc
 */
class WallEntryCalendar extends WallStreamModuleEntryWidget
{
    /**
     * @inheritdoc
     */
   public $editRoute = "/external_calendar/calendar/edit";

    /**
     * @inheritdoc
     */
   public $editMode = self::EDIT_MODE_NEW_WINDOW;

    /**
     * @var ExternalCalendar
     */
   public $model;

    /**
     * @return string returns the content type specific part of this wall entry (e.g. post content)
     */
    protected function renderContent()
    {
        Assets::register($this->getView());

        return $this->render('wallEntryCalendar', [
            'calendar' => $this->model
        ]);
    }

    public function getControlsMenuEntries()
    {
        $this->renderOptions->disableControlsEntrySwitchVisibility();
        return parent::getControlsMenuEntries();
    }


    /**
     * @return string a non encoded plain text title (no html allowed) used in the header of the widget
     */
    protected function getTitle()
    {
        return $this->model->title;
    }
}
