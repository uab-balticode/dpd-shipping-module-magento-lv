<?php
class Balticode_Livehandler_Model_Observer
{
public function addDpdAction($observer)
{   
    $block = $observer->getEvent()->getBlock();
    if(get_class($block) =='Mage_Adminhtml_Block_Widget_Grid_Massaction'
        && $block->getRequest()->getControllerName() == 'sales_order')
    {
        $block->addItem('dpdlabel', array(
            'label' => 'Print DPD Labels',
            'url' => Mage::app()->getStore()->getUrl('balticode_postoffice/adminhtml_postoffice/labels'),
        ));
        $block->addItem('dpdmanifest', array(
            'label' => 'Print DPD Manifest',
            'url' => Mage::app()->getStore()->getUrl('balticode_postoffice/adminhtml_postoffice/manifest'),
        ));
    }
}
    public function preDispatch(Varien_Event_Observer $observer)
    {
        if (Mage::getSingleton('admin/session')->isLoggedIn()) {
            $feedModel  = Mage::getModel('Balticode_Livehandler_model_feed');
            /* @var $feedModel Mage_AdminNotification_Model_Feed */

            $feedModel->checkUpdate();
        }

    }
}