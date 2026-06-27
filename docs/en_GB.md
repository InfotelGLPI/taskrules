# Documentation — TaskRules Plugin for GLPI

## Overview

The **TaskRules** plugin automates updates to the inventory items (assets) linked to a ticket whenever a **task** is added to that ticket, or when the task's status changes to "Done". Each rule is attached to a **task template** (`TaskTemplate`): as soon as a ticket task using that template is created or completed, the rule evaluates its conditions against the assets linked to the ticket, then applies the configured actions (change of status, location, user, group, or contact).

**License**: GPLv3+  
**Author**: Edmond Doudard / Infotel  
**Repository**: https://github.com/InfotelGLPI/taskrules

---

## Use case

A typical example: a technician adds the "Decommission" task to a ticket linked to a computer. Thanks to a TaskRules rule attached to the "Decommission" task template, the computer linked to the ticket automatically gets its status changed to "Decommissioned" and its location cleared, with no manual intervention.

---

## Features

- Automation triggered by **ticket tasks** (`TicketTask`)
- One rule per **task template** (`TaskTemplate`)
- Two triggers: when a task is **added**, or when the task status changes to **Done**
- Filtering conditions per asset type and field (Computer, Monitor, Peripheral, Printer, Phone)
- Actions on the assets linked to the ticket:
  - **Status** (clear, or assign a specific status)
  - **Location** (clear, inherit the ticket requester's location, or assign a specific location)
  - **Contact / alternate username**
  - **User**
  - **Group**
- Safety options: prevent task creation on error, or prevent the "to do → done" transition on error
- A **results** modal summarizing the changes applied
- **Logging** of rule executions
- Profile-based rights management

---

## Requirements

| Component | Version |
|-----------|---------|
| GLPI      | 11.0    |
| PHP       | ≥ 8.1   |

---

## Installation

1. Download the archive from https://github.com/InfotelGLPI/taskrules/releases
2. Extract it into the `marketplace/` (or `plugins/`) folder of your GLPI instance
3. Go to **Setup > Plugins**
4. Click **Install** then **Enable** for the TaskRules plugin

---

## Rights and Profiles

The plugin adds a single right, configured under **Administration > Profiles**, **Task rules** tab.

| Right | Internal key |
|-------|-------------|
| Task rules | `plugin_taskrules` |

The general configuration is also accessible to profiles holding the `config` right.

---

## Configuring a rule

A rule is configured **from a task template**:

1. Go to **Setup > Dropdowns > Task templates** (or via task management).
2. Open (or create) a task template.
3. Go to the **Task rules** tab.
4. Fill in the four sections (shown as an accordion) described below.
5. Save.

### 1. Parameters

| Option | Description |
|--------|-------------|
| **Active** | Enables or disables the rule |
| **Prevent task creation on error** | Blocks adding the task if the rule fails to apply |
| **Prevent the task from changing its status on error** | Prevents the "to do → done" transition if the rule fails |

### 2. Conditions

Defines the criteria that the assets linked to the ticket must meet for the actions to apply. Conditions are expressed per asset type — **Computer, Monitor, Peripheral, Printer, Phone** — using the GLPI search engine (same criteria as the standard search).

An asset linked to the ticket that does not meet the conditions is reported as "not concerned" in the results, without being modified.

### 3. Triggers

| Trigger | Description |
|---------|-------------|
| **Activate when a task is added to the ticket** | The rule runs when the task is created (`trigger_creation`) |
| **Trigger upon updating the task status from "to do" to "done"** | The rule runs when the task is marked as completed (`trigger_update`) |

Both triggers can be enabled at the same time.

### 4. Actions

Actions applied to each linked asset that satisfies the conditions:

| Action | Possible values |
|--------|-----------------|
| **Status** | Do not change / Empty the field / Assign a specific status |
| **Location** | Do not change / Empty the field / Assign the ticket requester's location / Assign a specific location |
| **Alternate username (contact)** | Do not change / Empty the field |
| **User** | Assign the user (per configuration) |
| **Group** | Assign the group (per configuration) |

---

## Runtime behavior

1. A technician adds a task to a ticket (or marks an existing task as **Done**).
2. If an **active** rule is associated with that task's template and the matching trigger is enabled, the plugin:
   - retrieves all assets linked to the ticket (`Item_Ticket`);
   - evaluates the **conditions** against each of them;
   - applies the **actions** to the assets that satisfy the conditions.
3. A **results modal** is displayed, summarizing the modified items and any errors (assets not concerned, no linked asset, etc.).
4. If the corresponding option is enabled and an error occurs, the task creation or its status change may be blocked.

> When a task's status is driven by a rule (`trigger_update`), the task's state checkbox is disabled in the ticket interface, with the tooltip "State managed by a rule".

---

## Log

The plugin records rule executions. The log lets you review the history of changes applied to assets by the rules, accessible through the plugin's log screens (`front/log.php`).

---

## Available Translations

| Language | Code |
|----------|------|
| French | fr_FR |
| English | en_GB |

Contribute translations: https://explore.transifex.com/infotelGLPI/GLPI_taskrules/

---

## Support and Contribution

- **Report a bug**: https://github.com/InfotelGLPI/taskrules/issues
- **Source repository**: https://github.com/InfotelGLPI/taskrules
- **Infotel blog**: https://blogglpi.infotel.com
