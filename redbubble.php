<?php

    if (isset($_REQUEST['user']) && $_REQUEST['user'] != '' && isset($_REQUEST['rbid']) && $_REQUEST['rbid'] != '') {
        $rbid = $_REQUEST['rbid'];
        $url  = sprintf('http://www.redbubble.com/people/%s/art/', $_REQUEST['user']);

        if ($xhtml = @file_get_contents($url . $rbid)) {
            $data = array();

            $doc = new DOMDocument();
            $doc->loadHTML($xhtml);
            if ($el = $doc->getElementById('work')) {
                if ($h2 = $el->getElementsByTagName('h2')) {
                    $data['Name'] = $h2->item(0)->nodeValue;
                }
                if ($ps = $el->getElementsByTagName('p')) {
                    $data['Description'] = '';
                    for ($i=0; $i < $ps->length; $i++) {
                        $data['Description'].= ($data['Description'] ? "\n\n" : "") . $ps->item($i)->nodeValue;
                    }
                }
            }
            if ($el = $doc->getElementById('tags')) {
                if ($a = $el->getElementsByTagName('a')) {
                    $data['tags'] = array();
                    for ($i=0; $i < $a->length; $i++) {
                        $data['tags'][] = $a->item($i)->nodeValue;
                    }
                }
            }
            if ($el = $doc->getElementById('buy')) {
                if ($a = $el->getElementsByTagName('a')) {
                    $data['BuyURL'] = $a->item(0)->getAttribute('href');
                }
            }
        }

        $xml = '<?xml version="1.0" encoding="utf-8"?>';
        if (isset($data) && is_array($data)) {
            $xml.= '<redbubbleart>'
                 . '<images>'
                 . '<small>' . sprintf('http://images-%d.redbubble.net/img/art/cropped/size:small/view:main/%s.jpg', rand(1,3), $rbid) . '</small>'
                 . '<medium>' . sprintf('http://images-%d.redbubble.net/img/art/size:medium/view:main/%s.jpg', rand(1,3), $rbid) . '</medium>'
                 . '<large>' . sprintf('http://images-%d.redbubble.net/img/art/size:large/view:main/%s.jpg', rand(1,3), $rbid) . '</large>'
                 . '<xlarge>' . sprintf('http://images-%d.redbubble.net/img/art/size:xlarge/view:main/%s.jpg', rand(1,3), $rbid) . '</xlarge>'
                 . '</images>';
            foreach ($data as $key=>$val) {
                if ($key == 'tags') {
                    $xml.= '<tags>';
                    foreach ($val as $tag) {
                        $xml.= '<tag>' . $tag . '</tag>';
                    }
                    $xml.= '</tags>';
                } else {
                    $xml.= '<' . $key . '>' . $val . '</' . $key . '>';
                }
            }
            $xml.= '</redbubbleart>';
        } else {
            $xml.= '<notice>Could not get data for this redbubble id</notice>';
        }

        header('Content-Type: text/xml');
        print $xml;
        exit;
    } else if (isset($_REQUEST['user']) && $_REQUEST['user'] != '') {
        $indexurl = sprintf('http://www.redbubble.com/people/%s/art?page=', $_REQUEST['user']);
        $imgurl   = 'http://images-1.redbubble.net/img/art/size:small/view:main/';
        $firstimg = NULL;
        $i = 0;
        $rbids = array();
        while (++$i) {
            if ($data = @file_get_contents($indexurl . $i)) {
                $doc = new DOMDocument();
                $doc->loadHTML($data);

                if ($el = $doc->getElementById('works')) {
                    if ($img = $el->getElementsByTagName('img')) {
                        for ($o=0; $o < $img->length; $o++) {
                            if ($src = $img->item($o)->getAttribute('src')) {
                                $rbid = substr($src, strlen($imgurl), -4);
                                if (!$firstimg) {
                                    $firstimg = $rbid;
                                } else if ($firstimg == $rbid) {
                                    break 2;
                                }
                                $rbids[] = $rbid;
                            }
                        }
                    }
                } else {
                    break;
                }
            } else {
                break;
            }
        }

        $xml = '<?xml version="1.0" encoding="utf-8"?>';
        if (!empty($rbids)) {
            $xml.= '<redbubbleIDs>';
            foreach ($rbids as $rbid) {
                // TODO: should be xml entitising RB id below
                $xml.= '<redbubbleID>' . $rbid . '</redbubbleID>';
            }
            $xml.= '</redbubbleIDs>';
        } else {
            $xml.= '<notice>No art was found for this account</notice>';
        }

        header('Content-Type: text/xml');
        print $xml;
        exit;
    } else {
        $xhtml = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"\n"
               . "          \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n"
               . "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n"
               . "<head>\n"
               . "    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />\n"
               . "    <title>RedBubble API Example</title>\n"
               . "</head>\n"
               . "<body>\n"
               . "<form method=\"get\">\n"
               . "<table>\n"
               . "<tr><th>RedBubble Username:</th><td><input type=\"text\" name=\"user\" /></td></tr>\n"
               . "<tr><th>RedBubble Photo ID:</th><td><input type=\"text\" name=\"rbid\" /></td></tr>\n"
               . "<tr><th>&nbsp;</th><td><input type=\"submit\" value=\"Go\" /></td></tr>\n"
               . "</table>\n"
               . "</form>\n"
               . "</body>\n"
               . "</html>\n";

        print $xhtml;
    }
