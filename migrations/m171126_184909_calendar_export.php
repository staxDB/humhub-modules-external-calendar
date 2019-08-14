<?php

use humhub\components\Migration;

class m171126_184909_calendar_export extends Migration
{
    public function up()
    {
        $this->createTable('external_calendar_export', [
            'id' => 'pk',
            'user_id' => $this->integer()->notNull(),
            'name' => $this->string(100)->notNull(),
            'token' => $this->string(45)->notNull(),
            'filter_participating' => $this->boolean()->defaultValue(0),
            'filter_mine' => $this->boolean()->defaultValue(0),
            'filter_only_public' => $this->boolean()->defaultValue(0),
            'include_profile' => $this->boolean()->defaultValue(1),
            'space_selection' => $this->integer()
        ], '');

        $this->createTable('external_calendar_export_spaces', [
            'id' => 'pk',
            'calendar_export_id' => $this->integer()->notNull(),
            'space_id' => $this->integer()->notNull(),
        ]);

        try {
            $this->addForeignKey('fk-calendar-export-user-id', 'external_calendar_export', 'user_id', 'user', 'id', 'CASCADE', 'CASCADE');
        } catch (Exception $ex) {
            Yii::error($ex->getMessage());
        }

        try {
            $this->addForeignKey('fk-calendar-export-spaces-id', 'external_calendar_export_spaces', 'calendar_export_id', 'external_calendar_export', 'id', 'CASCADE', 'CASCADE');
        } catch (Exception $ex) {
            Yii::error($ex->getMessage());
        }

        try {
            $this->addForeignKey('fk-calendar-export-spaces-space-id', 'external_calendar_export_spaces', 'space_id', 'space', 'id', 'CASCADE', 'CASCADE');
        } catch (Exception $ex) {
            Yii::error($ex->getMessage());
        }


//        $this->addForeignKey('fk-calendar-entry-calendar', 'external_calendar_entry', 'calendar_id', 'external_calendar', 'id', 'CASCADE','CASCADE');
    }

    public function down()
    {
        echo "m171126_184909_calendar_export does not support migration down.\n";
        return false;
    }

    /*
      // Use safeUp/safeDown to do migration with transaction
      public function safeUp()
      {
      }

      public function safeDown()
      {
      }
     */
}
