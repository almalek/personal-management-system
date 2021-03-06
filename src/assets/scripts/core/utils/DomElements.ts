import BackendStructureLoader   from "../ui/BackendStructure/BackendStructureLoader";
import Ajax                     from "../ajax/Ajax";

export default class DomElements {

    /**
     * @description Checks if there are existing elements for domElements selected with $();
     *
     * @param elements
     * @returns {boolean}
     */
    public static doElementsExists(elements): boolean
    {
        return 0 !== $(elements).length;
    }

    /**
     * @description This function is a find() decorator but it will throw exception if element was not found
     *              This is needed as some functionality MUST be executed so missing child element is a bug
     *
     * @param element
     * @param selector
     * @returns {boolean}
     */
    public static findChild(element: JQuery, selector: string): JQuery
    {
        let childElement = $(element).find(selector);

        if( 0 === $(childElement).length)
        {
            throw({
                "message"        : "Could not find the selector for element.",
                "element"        : element,
                "selectorToFind" : selector
            })
        }

        return childElement;
    };

    /**
     * @description Fetches the form view for given form name and appends it to the targetSelector
     *
     * @param formName
     * @param targetSelector
     * @param callbackParam {function}
     */
    public static appendFormView(formName: string, targetSelector: string, callbackParam: Function): void
    {
        let ajax           = new Ajax();
        let $targetElement = $(targetSelector);

        if( 0 === $targetElement.length ){
            throw ({
                "message"   : "No element with given selector was found",
                "selector"  : targetSelector
            })
        }

        try{
            var backendStructure = BackendStructureLoader.getNamespace(BackendStructureLoader.STRUCTURE_TYPE_FORM, formName);
            var namespace        = backendStructure.getNamespace();
        }catch(Exception){
            throw({
                'message'   : "Could not load form namespace from data processors.",
                'formName'  : formName
            })
        }

        let callback = function(formView){
            $targetElement.append(formView);
            if( "function" === typeof callbackParam){
                callbackParam();
            }
            backendStructure.getCallback()();
        };

        ajax.getFormViewByNamespace(namespace, callback);
    };

    /**
     * @description This function will remove the closest element which matches the selector relative to $element
     * 
     */
    public static removeClosestSelectorParentForElement($element: JQuery, selector: string): void
    {
        let parentToRemove = $($element).closest(selector);
        parentToRemove.remove();
    }
}