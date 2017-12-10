<?php

if (!defined('WHMCS'))
    die('This file cannot be accessed directly');

function whmcsslack_config()
{
    return array(
      'name' => 'WHMCS-Slack',
      'description' => 'Receive Slack notifications based on WHMCS events.',
      'version' => '1.0',
      'author' => 'salscode',
      'language' => 'english',
      "fields" => array(
        "webhookUrl" => array ("FriendlyName" => "Slack Webhook", "Type" => "text", "Size" => "80", "Description" => "", "Default" => "", ),
        "new_ticket" => array ("FriendlyName" => "New Tickets", "Type" => "yesno", "Size" => "25", "Description" => "Notify when a new ticket is created.", ),
        "new_update" => array ("FriendlyName" => "Ticket Updates", "Type" => "yesno", "Size" => "25", "Description" => "Notify when a ticket is updated.", ),
        "new_update_admin" => array ("FriendlyName" => "Ticket Admin Reply", "Type" => "yesno", "Size" => "25", "Description" => "Notify when an admin replies to a ticket.", ),
        "new_client" => array ("FriendlyName" => "New Clients", "Type" => "yesno", "Size" => "25", "Description" => "Notify when a new client signs up.", ),
        "new_invoice" => array ("FriendlyName" => "New Invoices", "Type" => "yesno", "Size" => "25", "Description" => "Notify when an invoice is paid.", ),
    ));
}

function whmcsslack_activate()
{
    return array('status' => 'info', 'description' => 'WHMCS-Slack is active.');
}

function whmcsslack_deactivate()
{
    return array('status' => 'info', 'description' => 'WHMCS-Slack is deactive.');
}
