---
name: Bug report
about: Create a report to help us improve the plugin
title: "[BUG] Description of Issue"
labels: investigating
assignees: ''

---

**Describe the bug**
A clear and concise description of what the bug is.

**To Reproduce**
Steps to reproduce the behavior:
1. Go to '...'
2. Click on '....'
3. Scroll down to '....'
4. See error

**Expected behavior**
A clear and concise description of what you expected to happen.

**Screenshots**
If applicable, add screenshots to help explain your problem.

**Operating environment (please complete the following information):**
- Plugin version:
- WooCommerce version:
- Wordpress version:
- PHP version:

**Things to verify before submitting a ticket**
- Verify you are using the most up to date [plugin version](https://wordpress.org/plugins/mailchimp-for-woocommerce/).
- Are there any fatal errors in WooCommerce? (WooCommerce -> Status -> Logs)
- If you're using the current version of the plugin, it utilizes a queue powered by [Action Scheduler](https://actionscheduler.org/). It depends on `WP_CRON` to be activated with your hosting provider. Please confirm that it's enabled.
- If you're using a host that makes use of CPU throttling, check to see if you've hit your limit after initiating the sync.
- Do you have any caching plugins or services running? If you're using Redis, Nginx, or MemCache, see if you or your hosting provider can exclude certain paths to the `REST API` and `/wp-json/mailchimp-for-woocommerce`. Visit our Wiki help page on [this topic](https://github.com/mailchimp/mc-woocommerce/wiki/Using-Caches) for more information.
- If you have a large number of plugins being used, you may need to bump up your memory limit on your server (1GB for example) to accommodate the initial sync.
