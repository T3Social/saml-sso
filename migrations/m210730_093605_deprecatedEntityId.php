<?php

use humhub\models\Setting;
use yii\db\Migration;

/**
 * Class m210730_093605_deprecatedEntityId
 */
class m210730_093605_deprecatedEntityId extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $enabledSetting = Setting::findOne([
            'module_id' => 'saml-sso',
            'name' => 'isEnabled',
        ]);
        if (!empty($enabledSetting->value)) {

            Setting::deleteAll(['module_id' => 'saml-sso', 'name' => 'spUseDeprecatedEntityId']);

            $deprecatedIdSetting = new Setting();
            $deprecatedIdSetting->module_id = 'saml-sso';
            $deprecatedIdSetting->value = "1";
            $deprecatedIdSetting->name = 'spUseDeprecatedEntityId';
            $deprecatedIdSetting->save();

            Yii::$app->cache->flush();
        }

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m210730_093605_deprecatedEntityId cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210730_093605_deprecatedEntityId cannot be reverted.\n";

        return false;
    }
    */
}
