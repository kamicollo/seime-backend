<?php

namespace Seimas\scrapers;


class RegistrationDataScraper extends AbstractScraper {
	protected $pattern = '#http:\/\/www3\.lrs\.lt\/pls\/inter\/w5_sale\.reg\?p_reg_id=-?[0-9]+#';
}
