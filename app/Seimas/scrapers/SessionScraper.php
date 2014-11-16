<?php

namespace Seimas\scrapers;

class SessionScraper extends AbstractScraper {
	protected $pattern = '#http:\/\/www3\.lrs\.lt\/pls\/inter\/w5_sale\.ses_pos\?p_ses_id=(-?[0-9]+)#';	
}
