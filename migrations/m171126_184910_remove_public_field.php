<?php

use humhub\components\Migration;

class m171126_184910_remove_public_field extends Migration
{
    public function up()
    {
        $this->dropColumn('external_calendar', 'public');
    }

    public function down()
    {
        echo "m171126_184910_remove_public_field does not support migration down.\n";
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
