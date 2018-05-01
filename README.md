# org.civicoop.someoneelsepays

## Introduction

The functional requirement for this extension is that the funding customer (https://www.domusmedica.be/) has a lot of situations where an individual becomes a member or registers for an event but wants his/her company to pay for this.
Partially this is possible in core CiviCRM using soft credits (for memberships) but it is somewhat confusing. The user does not see the actual payer of the membership on the membership screens, and the invoice does not show the name of the actual member.

This extension allows:
* another contact than the member to pay for a membership
* another contact then the participant to pay for an event registration

The extension will show the actual contact paying in the membership and participant screens and will also add the name of the participant or member on the invoice.

The extension contains the following changes:
* a new API _Sep_ with the actions _get_ and _create_. This will allow organizations that create their own forms to process and retrieve other payers using this API.
* it introduces a soft credit type _send invoice to_ (required for the membership processing). This soft credit type is automatically used in the processing, but is not present in the UI for soft credit types in the contribution forms.
 
![Screenshot](/images/soft_credit_type.png)

* a little techno talk: although it does generate a soft credit (record in _civicrm_contribution_soft_) of the special soft credit type _send invoice to_ during the processing of the membership payment if there is another contact paying, the record is automatically deleted at the en of the membership payment processing.
* in the membership edit and add form within the CiviCRM UI, it is no longer possible to add a soft credit type but possible to select another payer.
* for online membership pages: it is possible to include a _send invoice to_ honor section for the online membership page configured within the CiviCRM UI.
* when viewing a membership, a tab _Someone Else Pays_ is added to the page if another contact pays for the membership. 

![Screenshot](/images/membership_view.png)

* when registering a participant for an event that is not free in the CiviCRM UI, it is possible to enter another contact that pays for the registration. 

![Screenshot](/images/participant_register.png)

* when viewing a registration, a tab _Someone Else Pays_ is added to the page if another contact pays for the registration. 

![Screenshot](/images/participant_view.png)

There are **NO** changes for the online registration pages for events. The funder of the extension uses Webform CiviCRM for registration pages, so they do not need the event registration. If any party wants to fund changes that would allow the event registration pages to allow Someone Else Pays (with predefined custom fields probably), drop me a mail (erik dot hommel at civicoop dot org).

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Requirements

* PHP v5.4+
* CiviCRM 4.7

## Installation (Web UI)

This extension has not yet been published for installation via the web UI.

## Installation (CLI, Zip)

Sysadmins and developers may download the `.zip` file for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
cd <extension-dir>
cv dl org.civicoop.someoneelsepays@https://github.com/FIXME/org.civicoop.someoneelsepays/archive/master.zip
```

## Installation (CLI, Git)

Sysadmins and developers may clone the [Git](https://en.wikipedia.org/wiki/Git) repo for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
git clone https://github.com/FIXME/org.civicoop.someoneelsepays.git
cv en someoneelsepays
```
