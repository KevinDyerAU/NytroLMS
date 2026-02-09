/**
 * Fix for @react-three/fiber augmenting React JSX IntrinsicElements
 * which causes SVG element className props (used by Lucide icons) to resolve as 'never'.
 *
 * @react-three/fiber omits 'line', 'path', 'audio', 'source' from ThreeElements
 * but the JSX namespace merge still causes type inference issues for SVG elements.
 *
 * This file ensures React.SVGProps are correctly typed for className usage.
 */
import type { SVGProps } from 'react';

declare module 'react' {
  namespace JSX {
    interface IntrinsicElements {
      // Re-declare SVG elements that conflict with Three.js types
      svg: React.DetailedHTMLProps<React.SVGAttributes<SVGSVGElement>, SVGSVGElement>;
      line: React.SVGProps<SVGLineElement>;
      path: React.SVGProps<SVGPathElement>;
      circle: React.SVGProps<SVGCircleElement>;
      rect: React.SVGProps<SVGRectElement>;
      polyline: React.SVGProps<SVGPolylineElement>;
      polygon: React.SVGProps<SVGPolygonElement>;
    }
  }
}
