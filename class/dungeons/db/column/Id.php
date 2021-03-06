<?php //>

namespace dungeons\db\column;

class Id extends Serial {

    public function __construct($values = []) {
        parent::__construct($values);

        $this->formStyle('hidden');
        $this->sequence('base_id');

        $this->table()->id($this->name());
    }

}
