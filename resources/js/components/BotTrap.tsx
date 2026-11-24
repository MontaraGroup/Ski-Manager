/**
 * A field people never see; simple bots fill in every field in the HTML. App\Filters\BotTrap turns
 * away a sign-up, login or magic-link post that has it filled in.
 *
 * display:none and a name that matches no autofill pattern keep browsers and password managers from
 * filling it for real people: they only fill fields that can take focus, and guess the rest from
 * names like "company", "website" or "email".
 */
export function BotTrap() {
  return (
    <div aria-hidden="true" style={{ display: 'none' }}>
      <label htmlFor="hp_note_x7">Leave this empty</label>
      <input id="hp_note_x7" type="text" name="hp_note_x7" tabIndex={-1} autoComplete="off" defaultValue="" />
    </div>
  );
}
