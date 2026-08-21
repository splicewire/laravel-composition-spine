<?php

/*
|--------------------------------------------------------------------------
| Editorial angles
|--------------------------------------------------------------------------
|
| Merged under `splicewire.composition.angles` by CompositionDataServiceProvider.
|
| These live HERE, in the spine, because the spine owns `AngleComposer`, `ComposedShape`
| and the `AngleContributor` port. They were previously declared in the ENGINE's config
| while being read by the spine — a package reading a namespace another package registers,
| which is the same inversion that put the port itself on the wrong side of the boundary.
|
| The keys are also namespaced under the product root (`splicewire.composition.*`) rather
| than a package-shaped one. The code used to read `composition-engine.*`, a namespace no
| package has ever registered — the engine merges its file as `splicewire.composition`. So
| both reads silently returned nothing: contributors were always empty and the neutral base
| always null. Nothing caught it because AngleComposer has no production caller yet.
|
| The host supplies the concrete angle vocabulary; nothing here names an angle.
*/

return [

    /*
     | AngleContributor class-strings the composer merges, resolved through the container.
     | Order is not significant here — the CALLER's requested order decides precedence, with
     | the first requested angle supplying the composed intent and title guidance.
     */
    'contributors' => [],

    /*
     | The neutral base contributor: the shape a piece gets when it carries no editorial angle,
     | either because none was requested or because none matched. Optional — leave it null and
     | an unmatched compose yields an empty shape flagged `neutralBase` instead.
     |
     | Named `neutral_base` to match the vocabulary the code and its consumers already speak
     | (`ComposedShape::$neutralBase`, ShapeBeatGuidance's branch). It was `fallback_angle`,
     | which read as "the angle used when angles fail" and invited the reasonable-but-wrong
     | conclusion that the composer handles one angle at a time. It does not: `compose()` takes
     | an ordered list and merges all of them. This is the zero-angle case, not the one-angle case.
     |
     | Deliberately NOT empty in practice: a neutral base still carries sections and a prompt
     | fragment, which is why neutrality is stamped on the shape rather than inferred from it.
     */
    'neutral_base' => null,

];
