Payment module for Opencart 3.x.x - PayMaster

The following settings must be made in the Paymaster LC:
1. List of sites -> Settings -> Block "Technical parameters"
    Select "Signature Type" recommended by SHA256
    Enter "Secret Key"
Click "Save"


2. List of sites -> Settings -> Block "Callbacks"
    Payment Notification: select a POST request and insert the edited link in the appeared field:
    http: //YOUR_SITE/index.php? route = extension / payment / paymaster / callback
    Success redirect: select a POST request and paste the edited link in the field that appears:
    http: //YOUR_SITE/index.php? route = extension / payment / paymaster / success
    Failure redirect: select a POST request and paste the edited link in the field that appears:
    http: //YOUR_SITE/index.php? route = extension / payment / paymaster / fail
Click "Save"


Installing the module:

1. Upload the archive files (contents of the upload folder) to the site root.

2. Log into the admin panel (Add-ons / Add-ons), select the add-on type "Payments", activate the Paymaster module.

4. Click the "Change" button.
    Enter the Identifier in the fields that appear - LMI_MERCHANT_ID (from the PayMaster personal account (List of sites / ID)).
    Select "Data encryption method" (exactly the same as in your PayMaster personal account)
    Enter the "Secret Key" (exactly the same as in your PayMaster personal account)
    Select "Order status after payment" (the order will be transferred to this status after successful payment on the PayMaster website)
    Change "Status" to "Enabled"

Click the "Save" button.

To work with an online checkout: Add tax classes

1.Tax rates in tax classes must be empty

2. Enter the settings of the Paymaster module

3. We assign the tax class to the tax rate

4. Add tax class to all products

5. Turn on tax accounting 

