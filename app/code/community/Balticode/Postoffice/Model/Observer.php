<?php
class Balticode_Postoffice_Model_Observer
{
    public function preDispatch(Varien_Event_Observer $observer)
    {
        if (Mage::getSingleton('admin/session')->isLoggedIn()) {
            $feedModel  = Mage::getModel('Balticode_Postoffice_model_feed');
            /* @var $feedModel Mage_AdminNotification_Model_Feed */

            $feedModel->checkUpdate();
        }

    }
}