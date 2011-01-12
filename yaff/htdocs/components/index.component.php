<?php
/**
    Bare-bones component.  The index component handles
    requests to '/'.
    Since '/' has no subpaths, this component should only
    have a index() method.

    Try the tutorial for more information on components.
*/

class IndexComponent extends Component{
    
    public static function index($ctx){
        return 'Hello World';
    }

}
