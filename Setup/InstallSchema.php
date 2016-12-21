<?php
/**
 * Copyright © 2016 Codaone Oy. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Codaone\LogitrailModule\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $table_names = array(
            'quote',
            'sales_order');

        foreach ($table_names as $table_name) {
            $installer->getConnection()
            ->addColumn($installer->getTable($table_name), 'logitrail_order_id', array(
                    'type'      =>  \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable'  => true,
                    'length'    => 255,
                    'after'     => null, // column name to insert new column after
                    'comment'   => 'Logitrail Order ID'));

        }

        $installer->endSetup();
    }
}
