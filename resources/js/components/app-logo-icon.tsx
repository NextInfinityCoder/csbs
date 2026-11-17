import type { SVGAttributes } from 'react';

/**
 * The CSBS mark: a crest with a rooster comb above a shield split into
 * two halves for the two sides of a fight.
 */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
            <path d="M10.5 11.2L12.1 6.3Q12.8 4.6 14.2 5.8L15.9 7.3L18.4 2.4Q19.3 0.9 20.5 2.3L22.9 6.9L24.8 5Q26.2 3.8 26.9 5.5L29.5 11.2Z" />
            <path d="M6 13.4H18.7V37.4C11.5 35.2 6 29 6 21.1Z" />
            <path d="M21.3 13.4H34V21.1C34 29 28.5 35.2 21.3 37.4Z" />
        </svg>
    );
}
