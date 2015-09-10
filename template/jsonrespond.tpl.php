<?php
DAssert::assert($tpl_jsonRespond instanceof MJsonRespond);

print $tpl_jsonRespond->toJson();
