SSL Subdomain for Multisite
===========================

Ensures logins are always done via SSL on a subdomain of the master domain, but that access to custom domains are always done over HTTP, to avoid certificate errors. For WordPress Multisite.

WordPress’ built-in `FORCE_SSL_LOGIN` and `FORCE_SSL_ADMIN` directives in `wp-config.php` work great, but are too restrictive in situations where you have custom domains — custom domains for which you cannot have SSL certificates. For sites on custom domains, we need to make sure that the login and admin access happens over the subdomain, which can be properly served over HTTPS.

What this Plugin does (incorporating Foolish Assumptions)
---------------------------------------------------------

 > *or, a readme you **should** read before implementing this plugin on your site.*
 
This plugin filters the login, logout, home and admin URLs that WordPress uses to generate its internal links.

Why do we do this?

### The Scenario

We have a WordPress Multisite network. Let's call it `mynetwork.com`. We bought a fancy SSL wildcard certificate so we can offer `*.mynetwork.com` over a secure connection.

We’d very much like to use this secure connection for all logins, and for all admin access.

We also allow sites on this network to use a custom domain — like `demo-site.com`. We might be using [WPMU Domain Mapping](https://wordpress.org/extend/plugins/wordpress-mu-domain-mapping/) to achieve this. These sites have two domains, then — `demo-site.com` and `demo-site.mynetwork.com`.

If we switch on `FORCE_SSL_LOGIN` or `FORCE_SSL_ADMIN`, we have a problem. When users go to `https://demo-site.com/wp-login.php`, they get a certificate error. We have a wildcard certificate for `*.mynetwork.com`, but we can’t possibly have a valid SSL certificate installed for every custom domain!

Instead, we want to force all login pages and admin pages to be:

`https://demo-site.mynetwork.com/wp-admin/`…

We want all regular access to be:

`http://demo-site.com/`…

This plugin facilitates that — rewriting the `wp-login` (including logout) and `wp-admin` URLs to the first example, and rewriting all the others to the second style.

### Foolish Assumptions

This plugin makes some (foolish) assumptions about your multisite network. You must make sure that these assumptions are true for your site, or you will find that this plugin may have unintended consequences and break things that are difficult to fix without manually disabling the plugin.

1.	Your subdomains are in the format: `a.b`
		> where `a` is, for example: `demo-site`.
		> `b` is, for example: `mynetwork.com`.
		> `a` **must** be a single domain component. (`a` can't be, for example: `demo.site`)
	
			
2.	Your wildcard certificate is configured properly for:
		
	*	`*.mynetwork.com`
	*	your network site URL
	
	So if your network site URL is `www.mynetwork.com`, your wildcard cert will be fine. If it is just `mynetwork.com`, you will need another cert to avoid errors when you go to `https://mynetwork.com/wp-admin`.)
				
3.	Your site already has the custom domains working.
		
	*	I suggest the excellent [WPMU Domain Mapping](https://wordpress.org/extend/plugins/wordpress-mu-domain-mapping/) for this.
		
		*	Your web server also needs to be [set up](http://www.lampjunkie.com/2008/05/how-to-set-up-a-wildcard-catch-all-virtual-host-in-apache/ "A tutorial on wildcard catch all hosting for Apache") to handle hosting a wildcard name virtual host. All of this is really part of the WPMU Domain Mapping set up, and not the set up for this plugin. I’m just, you know, mentioning it.
			  
4.	You have the `FORCE_SSL_LOGIN` setting in `wp-config.php` **true**.
		
5.	You have the `FORCE_SSL_ADMIN` setting in `wp-config.php` **false**. We’ll handle that — WordPress’ forcing of SSL admins will conflict with this plugin.
