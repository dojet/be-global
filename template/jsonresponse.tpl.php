<?php
DAssert::assert($tpl_json instanceof MJson);

print $tpl_json->toJson();
