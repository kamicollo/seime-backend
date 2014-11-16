<?php

namespace Seimas\scrapers;

class VoteDataScraper extends AbstractScraper {
	protected $pattern = '#http:\/\/www3\.lrs\.lt\/pls\/inter\/w5_sale\.bals\?p_bals_id=(-?[0-9]+)#';	
}
