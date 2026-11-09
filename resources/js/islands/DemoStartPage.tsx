import { Icon } from '@/components/Icon';
import { PostButton } from '@/components/Page';

/** Behind every /demo link. Only the button creates a demo account */
export default function DemoStartPage() {
  return (
    <div className="mx-auto flex max-w-xl flex-col gap-4 p-4 lg:p-8">
      <h1 className="flex items-center gap-3 text-2xl font-semibold">
        <Icon name="fa-mountain-sun" className="text-accent" />
        Try a demo resort
      </h1>
      <p className="text-muted">
        No sign-up. You get a working ski resort with 4 lifts, 8 runs and 3 buildings. Save it to an account whenever you like.
      </p>
      <PostButton action="/demo" variant="primary" className="self-start">
        Start the demo
      </PostButton>
    </div>
  );
}
