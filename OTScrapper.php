<?php

require('phpQuery/phpQuery.php');

class OTScrapper
{

    private $url = "https://www.otaus.com.au/find-an-ot";
    private $member_search_url = "https://www.otaus.com.au/search/membersearchdistance";
    private $get_contact_url = "https://www.otaus.com.au/search/getcontacts?ids=";

    private $arr_firm = null;
    private $arr_prev_members_ids = null;
    private $arr_next_members_ids = null;

    private $filename = "";
    private $request_limit = 10;

    // ServiceType = 2  (Face to Face)
    private $member_search_post_fields = array('Distance' => '0', 'ServiceType' => '2', 'AreaOfPracticeId' => '',
        'Name' => '', 'State' => '0', 'Location' => '', 'FundingSchemeId' => '', 'PracticeName' => '');

    private $arr_firm_data_header = array('Practice Name', 'Contact Name', 'Address Street', 'Address City',
        'Address State', 'Address PostCode', 'Address Country', 'Phone', 'Funding Scheme', 'Area(s) of Practice');

    public function  __construct($filename)
    {
        $this->filename = $filename;
    }

    public function scrap()
    {
        try {
            $arr_area_of_practices = $this->getAreaOfPractices();

            if (is_null($arr_area_of_practices)) return false;

            //for some datas
//            $arr_area_of_practices = array('01e57934-651f-e911-9ccb-441ca8ff2986', 'd78ec22f-484e-4812-85a4-d2a6ced662af');

            foreach ($arr_area_of_practices as $aop) {

                $this->member_search_post_fields['AreaOfPracticeId'] = $aop;
                $member_search_response = $this->getMemberSearchJson($this->member_search_post_fields);

                echo 'Processing member search details ', $this->getCurrentDateTime(), "\n";
                logInfo('Processing member search details ' . $this->getCurrentDateTime());

                if (is_null($this->arr_prev_members_ids)) {
                    if (isset($member_search_response->mainlist)) $this->arr_prev_members_ids = $member_search_response->mainlist;

                    $this->saveToCSV($this->arr_firm_data_header);

                    // get member details with 10 records at a time
                    $this->getContactDetailsAndSave($this->arr_prev_members_ids);

                } else {

                    if (isset($member_search_response->mainlist)) $this->arr_next_members_ids = $member_search_response->mainlist;

                    // Exclude member ids if in prev list
                    $this->arr_next_members_ids = array_diff($this->arr_next_members_ids, $this->arr_prev_members_ids);

                    // Update prev with new entries
                    $this->arr_prev_members_ids = array_merge($this->arr_prev_members_ids, $this->arr_next_members_ids);

                    // Scrap next new members entries
                    $this->getContactDetailsAndSave($this->arr_next_members_ids);
                }

                unset($this->arr_next_members_ids);

                sleep(rand(10, 20));
            }
            return true;

        } catch (Exception $e) {
            logError('Exception : scrap, ErrCode: ' . $e->getCode() . ' ,Message : ' . $e->getMessage());
            return false;
        }
    }

    private function getAreaOfPractices()
    {
        try {
            //// get area of practice
            $document = curl($this->url);

            phpQuery::newDocument($document);

            $dom_area_of_practices = pq('select[id=memberSearch_AreaOfPracticeId]')->find('option');
            $arr_area_of_practices = array();

            foreach ($dom_area_of_practices as $ap) {
                array_push($arr_area_of_practices, pq($ap)->val());
            }
            $arr_area_of_practices = array_filter($arr_area_of_practices);

            return $arr_area_of_practices;

        } catch (Exception $e) {
            logError('Exception : getAreaOfPractices, ErrCode: ' . $e->getCode() . ' ,Message : ' . $e->getMessage());
            return null;
        }
    }

    private function getMemberSearchJson($post_fields)
    {
        try {
            $member_search_response = curl($this->member_search_url, "post", $post_fields);
            return json_decode($member_search_response);
        } catch (Exception $e) {
            logError('Exception : getMemberSearchJson, ErrCode: ' . $e->getCode() . ' ,Message : ' . $e->getMessage());
            return null;
        }
    }

    private function getContactDetailsAndSave($member_ids)
    {
        try {

            if (is_null($member_ids)) return;

            $from = 0;
            $to = $this->request_limit;
            $arr_count = count($member_ids);

            echo 'Processing contact details ', $this->getCurrentDateTime(), "\n";
            logInfo('Processing contact details ' . $this->getCurrentDateTime());

            while ($arr_count > 0) {

                //get contact details for ids
                $ids = array_slice($member_ids, $from, $to, true);
                $get_contact_url = $this->get_contact_url . join("&ids=", $ids);
                $contact_details_dom = curl($get_contact_url);

                phpQuery::newDocument($contact_details_dom);

                $content = pq('div[class=results__item]');

                $prev_id = null;
                $arr_firm_data = null;

                foreach ($content as $con) {

                    if (!is_null($prev_id) && $prev_id === trim(pq($con)->find('div.org-main-content > div.content__row > div.main-contact-content')->attr('ajax-data')))
                        continue;

                    $prev_id = trim(pq($con)->find('div.org-main-content > div.content__row > div.main-contact-content')->attr('ajax-data'));

                    $firm_data['practice_name'] = $this->sanitize(pq($con)->find('div.org-main-content > div.content__row > div.main-contact-content > div.title__tag')->html());
                    $firm_data['contact_name'] = $this->sanitize(pq($con)->find('div.org-main-content > div.content__row > div.main-contact-content strong[class=name]')->html());

                    $address = pq($con)->find('div.org-main-content > div.content__row > div.main-contact-content > p:eq(1)')->html();
                    $address = $this->getAddressSeparately($address);

                    $firm_data = array_replace($firm_data, $address);

                    $firm_data['phone'] = $this->sanitize(pq($con)->find('div.org-main-content > div.content__row > div.main-contact-content a[href^=tel]')->html());

                    $funding_scheme_area_dom = pq($con)->find('div.org-main-content > div.content__row > div.content__col:eq(1) p')->html();
                    $funding_scheme_area_dom = explode("<br>", $funding_scheme_area_dom);

                    $firm_data['funding_scheme'] = "";
                    $firm_data['area_of_practices'] = "";

                    foreach ($funding_scheme_area_dom as $fsa_dom) {
                        if (preg_match("/<strong>Funding/", $fsa_dom))
                            $firm_data['funding_scheme'] = $this->sanitize(preg_replace("/<strong>.*?<\/strong>/", '', $fsa_dom));

                        if (preg_match("/<strong>Area/", $fsa_dom))
                            $firm_data['area_of_practices'] = $this->sanitize(preg_replace("/<strong>.*?<\/strong>/", '', $fsa_dom));
                    }

                    $this->saveToCSV($firm_data);

                    if (trim(pq($con)->find('div.org-main-content > div.extra__content ')->html()) !== '') {

                        $extra_contents_row = pq($con)->find('div.org-main-content > div.extra__content div.content__row');

                        if (isset($extra_contents_row) && count($extra_contents_row) > 0) {
                            $counter = count($extra_contents_row);
                            for ($i = 0; $i < $counter; $i++) {
                                $eq = ' div.content__row:eq(' . $i . ')';
                                $firm_data['phone'] = $this->sanitize(pq($con)->find('div.org-main-content > div.extra__content ' . $eq . ' a[href^=tel]')->html());
                                $firm_data['practice_name'] = $this->sanitize(pq($con)->find('div.org-main-content > div.extra__content ' . $eq . ' div.contact-content div.title__tag')->html());
                                $address = pq($con)->find('div.org-main-content > div.extra__content ' . $eq . ' div.contact-content > p:eq(0)')->html();
                                $address = $this->getAddressSeparately($address);
                                $firm_data = array_replace($firm_data, $address);
                                $firm_data['funding_scheme'] = '';
                                $firm_data['area_of_practices'] = '';
                                $this->saveToCSV($firm_data);
                            }
                        }
                    }
                    unset($arr_firm_data);
                }

                $from += $this->request_limit;
                $arr_count -= $this->request_limit;

                sleep(rand(10, 20));
            }
        } catch (Exception $e) {
            logError('Exception : getContactDetailsAndSave, ErrCode: ' . $e->getCode() . ' ,Message : ' . $e->getMessage());
        }
    }

    private function sanitize($param)
    {
        return trim(html_entity_decode(preg_replace("/\s+/", " ", preg_replace("/\r|\n/", "", $param))));
    }


    private function getAddressSeparately($address)
    {
        $address = explode("<br>", $address);

        $arr_address['street'] = isset($address[0]) ? $this->sanitize($address[0]) : '';
        $city_state_postcode = isset($address[1]) ? explode(',', $this->sanitize($address[1])) : null;

        $state = '';
        $post_code = '';

        if (!is_null($city_state_postcode)) {
            foreach ($city_state_postcode as $key => $value) {
                if (preg_match("/(^[A-Z]{2}$)/", trim($value)) || preg_match("/(^[A-Z]{3}$)/", trim($value))) {
                    $state = $this->sanitize($value);
                    unset($city_state_postcode[$key]);
                }
                if (preg_match("/(^[0-9]+$)/", trim($value))) {
                    $post_code = $this->sanitize($value);
                    unset($city_state_postcode[$key]);
                }
            }
        }
        $arr_address['city'] = (!is_null($city_state_postcode)) ? rtrim($this->sanitize(implode(',', $city_state_postcode)), ',') : '';
        $arr_address['state'] = $state;
        $arr_address['post_code'] = $post_code;

        $arr_address['country'] = isset($address[2]) ? $this->sanitize($address[2]) : '';

        return $arr_address;

    }

    private function saveToCSV($arr)
    {
        $file = fopen($this->filename, "a+");

        fputcsv($file, $arr, ",", "\"");

        fclose($file);
        // fputcsv( file, fields, separator, enclosure, escape )
    }

    private function getCurrentDateTime()
    {
        return date('Y-m-d H:i:s');
    }

}
