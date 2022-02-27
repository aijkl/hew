export class SearchState
{
    public ErrorTargetMessage:string;
    public ErrorBodyMessage:string;
    public ErrorTagMessage:string;
    public DisableSubmitButton:boolean;
    public StateChanged: (() => void) | undefined;

    public constructor()
    {
        this.ErrorBodyMessage = "";
        this.ErrorTargetMessage = "";
        this.ErrorTagMessage = "";
        this.DisableSubmitButton = false;
    }

}