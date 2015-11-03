<?php
DAssert::assert($tpl_jsonResponse instanceof MJsonResponse);

print $tpl_jsonResponse->toJson();
