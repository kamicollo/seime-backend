<?php

namespace Seimas\scrapers;

class QuestionScraper extends AbstractScraper {
	protected $pattern = '#http:\/\/www3\.lrs\.lt\/pls\/inter\/w5_sale\.klaus_stadija\?p_svarst_kl_stad_id=(-?[0-9]+)#';	
}
