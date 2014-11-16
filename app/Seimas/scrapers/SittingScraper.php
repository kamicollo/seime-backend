<?php

namespace Seimas\scrapers;


class SittingScraper extends AbstractScraper {
	protected $pattern = '#http:\/\/www3\.lrs\.lt\/pls\/inter\/w5_sale\.fakt_pos\?p_fakt_pos_id=(-?[0-9]+)#';			
}
