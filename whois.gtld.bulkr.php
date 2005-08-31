<?php
/*
Whois.php        PHP classes to conduct whois queries

Copyright (C)1999,2005 easyDNS Technologies Inc. & Mark Jeftovic

Maintained by David Saez (david@ols.es)

For the most recent version of this package visit:

http://www.phpwhois.org

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

/* bulkregistercom.whois	1.0	mark jeftovic	1999/12/06 */
/* bulkregistercom.whois	1.1	Matthijs Koot	2003/01/14 */

if (!defined("__BULKR_HANDLER__"))
	define("__BULKR_HANDLER__", 1);


/*#################################################
Matthijs Koot - 2003/01/14 - matthijs[AT]koot[DOT]biz - http://www.koot.biz
--> BulkRegister V1.1 UPDATE NOTE:

BulkRegister.com has several antispam measures, which include
grammatic algorithms for changing their Whois output every
x request or every x seconds or so. This update includes
new regexp's to extract the information.

In addition, whois.bulkregister.com will only allow few
whois request to be made from the same IP-address within
a specific period of time. Exceeding requests will be
bounced!

I have tested it on dozens of domains, but as I'm a
regexp newbie it *might* be buggy - bugreports are welcome!

#################################################*/

require_once('whois.parser.php');

class bulkr_handler
	{

	function parse($data_str, $query)
		{
		$data_str = preg_replace("/\n+/", "_", implode("\n", $data_str));
		$data_str = preg_replace("/\s+/", " ", $data_str);

		//echo "BEGIN@".$data_str."@EINDE";

		preg_match("/terms\._(.+?)_(.+?)/", $data_str, $refs);
		$r["owner"]["organization"] = trim($refs[1]);

		preg_match("/terms\._(.+?)_(.*)_\sDomain Name/", $data_str, $refs);
		$r["owner"]["address"] = explode("_", trim($refs[2]));
		//preg_replace("/_/","\n",trim($refs[2]));

		preg_match("/terms\._.*_\s*Domain Name:\s(.+)_\sAdmin/", $data_str, $refs);
		$r["domain"]["name"] = trim($refs[1]);

		preg_match("/Administrative Contact(.+?)\s(.+?@.+?)_/", $data_str, $refs);
		preg_match("/_?(.*)[:->]*?\s(.*@.*)/", $refs[2], $refssub);
		while (preg_match("/[:\->]+?/", substr($refssub[1],  - 1)) > 0)
			{
			$refssub[1] = substr($refssub[1], 0, strlen($refssub[1]) - 1);
			}
		$r["admin"]["name"] = $refssub[1];
		$r["admin"]["email"] = $refssub[2];

		preg_match("/Technical Contact(.+?)\s(.+?@.+?)_/", $data_str, $refs);
		preg_match("/_?(.*)[:->]*?\s(.*@.*)/", $refs[2], $refssub);
		while (preg_match("/[:\->]+?/", substr($refssub[1],  - 1)) > 0)
			{
			$refssub[1] = substr($refssub[1], 0, strlen($refssub[1]) - 1);
			}
		$r["tech"]["name"] = $refssub[1];
		$r["tech"]["email"] = $refssub[2];

		preg_match("/Billing Contact(.+?)\s(.+?@.+?)_/", $data_str, $refs);

		if (isset($refs[2]))
			{
			preg_match("/_?(.*)[:->]*?\s(.*@.*)/", $refs[2], $refssub);
			while (preg_match("/[:\->]+?/", substr($refssub[1],  - 1)) > 0)
				{
				$refssub[1] = substr($refssub[1], 0, strlen($refssub[1]) - 1);
				}
			$r["billing"]["name"] = $refssub[1];
			$r["billing"]["email"] = $refssub[2];
			}

		preg_match("/Record (update|updated) (date|on)( on)?(  | -|: |->)?(.+?)_/", $data_str, $refs);
		$r["domain"]["changed"] = trim($refs[5]);

		preg_match("/Record (create|created) (date|on)( on)?(  | -|: |->)?(.+?)_/", $data_str, $refs);
		$r["domain"]["created"] = trim($refs[5]);

		preg_match("/Record (will|expire|expires|expiring)( on| date| be| expire)( expiring on date| on| date)?(  |: | -|->)?(.+?)_/", $data_str, $refs);
		$r["domain"]["expires"] = trim($refs[5]);

		//preg_match("/(Database last updated on|Database last updated on:) (.+?)\./",$data_str, $refs);
		//$r["regrinfo"]["db_updated"]=$refs[1];
		preg_match("/Domain servers in listed order:_ (.+)_Register/", $data_str, $refs);
		$ns = explode("_", $refs[1]);
		for ($i = 0, $max = count($ns); $i < $max; $i++)
			{
			$r["domain"]["nserver"][] = $ns[$i];
			}
		format_dates($r, 'ymd');
		return ($r);
		}

	}
?>
