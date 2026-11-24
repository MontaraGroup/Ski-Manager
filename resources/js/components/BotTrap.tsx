/**
 * A field people never see or reach with Tab; simple bots fill in every field. App\Filters\BotTrap
 * turns away a sign-up, login or magic-link post that has it filled in.
 */
export function BotTrap() {
  return (
    <div aria-hidden="true" className="absolute -left-[9999px] h-px w-px overflow-hidden">
      <label htmlFor="company_website">Leave this empty</label>
      <input id="company_website" type="text" name="company_website" tabIndex={-1} autoComplete="off" defaultValue="" />
    </div>
  );
}
