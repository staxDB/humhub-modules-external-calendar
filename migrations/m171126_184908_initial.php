<?php

use humhub\components\Migration;

class m171126_184908_initial extends Migration
{
    public function up()
    {
        $this->createTable('external_calendar', array(
            'id' => 'pk',
            'title' => 'varchar(255) NOT NULL',
            'description' => 'TEXT NULL',
            'url' => 'varchar(255) NOT NULL',
            'public' => 'tinyint(1) NOT NULL DEFAULT 0',
            'time_zone' => 'varchar(60) DEFAULT NULL',
            'color' => 'varchar(7)',
            'version' => 'varchar(10)',
            'cal_name' => 'varchar(255)',
            'cal_scale' => 'varchar(60)',
            'sync_mode' => 'tinyint(4) NOT NULL',
            'event_mode' => 'tinyint(4) NOT NULL',
        ), '');

        $this->createTable('external_calendar_entry', array(
            'id' => 'pk',
            'uid' => 'varchar(255) NOT NULL',
            'calendar_id' => 'int(11) NOT NULL',
            'title' => 'varchar(255) NOT NULL',
            'description' => 'TEXT NULL',
            'location' => 'varchar(255)',
            'last_modified' => 'datetime NOT NULL',
            'dtstamp' => 'datetime NOT NULL',
            'start_datetime' => 'datetime NOT NULL',
            'end_datetime' => 'datetime NOT NULL',
            'all_day' => 'tinyint(4) NOT NULL',
            'time_zone' => 'varchar(60) DEFAULT NULL',
        ), '');

//        $this->addForeignKey('fk-calendar-entry-calendar', 'external_calendar_entry', 'calendar_id', 'external_calendar', 'id', 'CASCADE','CASCADE');
    }

    public function down()
    {
        echo "m171126_184908_initial does not support migration down.\n";
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
