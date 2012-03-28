<?php
/*
Plugin Name: SSL Subdomain for Multisite
Plugin URI: http://www.vanpattenmedia.com/
Description: Ensures logins are always done via SSL on any subdomain of the master domain, but that access to custom domains are always done over HTTP, to avoid certificate errors. For WordPress Multisite.
Version: 1.0
Author: Peter Upfold
Author URI: http://peter.upfold.org.uk/
License: GPL2
*/
/*  Copyright (C) 2012 Peter Upfold.

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


/*********************************************************
			WHAT THIS PLUGIN DOES
		
		INCORPORATING
			FOOLISH ASSUMPTIONS
**********************************************************
		
		 (((((((((((((((((())))))))))))))))))))))))))))
		 (	or, a readme you *should* read	)))))))
		 (		before implementing	)))))))
		 (		this plugin on your site)))))))
		 (					)))))))
		 (((((((((((((((((())))))))))))))))))))))))))))			
		

This plugin filters the login, logout, home and admin URLs that WordPress
uses to generate its internal links.

Why do we do this?

Here's the scenario:

We have a WordPress Multisite network. Let's call it mynetwork.com. We bought
a fancy SSL wildcard certificate so we can offer *.mynetwork.com over a secure
connection.

We'd very much like to use this secure connection for all logins, and for all
admin access.

We also allow sites on this network to use a custom domain -- like demo-site.com.
We might be using WPMU Domain Mapping to achieve this. These sites have two domains,
then -- demo-site.com and demo-site.mynetwork.com.

We want all login pages and admin pages to be:

https://demo-site.mynetwork.com/wp-admin/...

We want all regular access to be:

http://demo-site.com/

This plugin facilitates that -- rewriting the login and logout and wp-admin URLs to
the first example, and rewriting all the others to the second style.

******************************************
	FOOLISH ASSUMPTIONS
******************************************

This plugin makes some (foolish) assumptions about your multisite network. You must
make sure that these assumptions are true for your site, or you will find that this
plugin may have unintended consequences and break things that are difficult to fix
without manually disabling the plugin.

		* Your subdomains are in the format:
				a.b
				
			where a is, for example: demo-site
				  b is, for example: mynetwork.com
				  
			a **must** be a single domain component.
			a can't be, for example: demo.site
			
		* Your wildcard certificate is configured properly for:
		
			> *.mynetwork.com
			> your network site URL
				(so if that is www.mynetwork.com, your wildcard
				cert is fine. If it is just mynetwork.com, you
				will need another cert for mynetwork.com configured
				to avoid cert errors when you go to
					https://mynetwork.com/wp-admin.
					
				If it's another cert, it needs to be on another IP.
				)
				
		* Your site already has the custom domains working.
		
			> I suggest the excellent WPMU Domain Mapping for this.
				<https://wordpress.org/extend/plugins/wordpress-mu-domain-mapping/>
			> Your web server also needs to be set up to handle hosting a wildcard
			  name virtual host. All of this is really part of the WPMU Domain Mapping
			  set up, and not the set up for this plugin. I'm just, you know, mentioning it.
			  
		* You have the FORCE_SSL_LOGIN setting in wp-config.php ON.
		
		* You have the FORCE_SSL_ADMIN setting in wp-config.php OFF. We'll handle that -- WordPress' forcing
		  of SSL admins will conflict with this plugin.
			  
	<http://www.lampjunkie.com/2008/05/how-to-set-up-a-wildcard-catch-all-virtual-host-in-apache/>

*****************************************/


function sslsubc_filter_custom_domain_login_url($url)
{
/*
	Filter the login/logout links so that they already point to the master domain's
	login page, with SSL. This avoids a rather ugly client-side redirect that WPMU
	Domain Mapping does to achieve this effect.
	
	(I love you really, WPMU Domain Mapping. It's nothing personal.)
*/

	$topHost = parse_url(network_site_url( '', 'https' )); // get master domain
	$topHost = $topHost['host'];

	$pUrl = parse_url($url);

	$originalDomain = parse_url(get_site_url());
	$originalDomain = explode('.', $originalDomain['host']);
	$originalDomain = $originalDomain[0]; // get this domain's bottom-level component
	
	
	if (!$pUrl)
		return $url;
		
	if ($pUrl['host'] != $topHost  && ( parse_url(get_site_url(), PHP_URL_HOST) != parse_url(network_site_url(), PHP_URL_HOST)  ) )
	{
		$url = 'https://'.$originalDomain.'.'.$topHost.$pUrl['path']; // reconstruct "bottom-level.master-domain/path"
		$url .= (!empty($pUrl['query']) ? '?'.$pUrl['query']: '');
		return $url;
	}
	else {
		return preg_replace('/^http:\/\//', 'https://', $url);
	}
	
}

function sslsubc_filter_home_url($url)
{
/*
	Filter the 'return to Site' home_url so that it points back to the custom domain without SSL,
	to dodge any cert errors that would otherwise occur.
*/

	return preg_replace('/^https:\/\//', 'http://', $url);

}

add_filter('login_url', 'sslsubc_filter_custom_domain_login_url');
add_filter('logout_url', 'sslsubc_filter_custom_domain_login_url');
add_filter('home_url', 'sslsubc_filter_home_url');
add_filter('admin_url', 'sslsubc_filter_custom_domain_login_url');
?>
