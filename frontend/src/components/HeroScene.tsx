/**
 * HeroScene - 3D floating bubble background matching NytroAI's QuantumScene
 * Uses react-three-fiber with distorted spheres, environment lighting, and float animation
 */
import React, { useRef } from 'react';
import { Canvas, useFrame } from '@react-three/fiber';
import { Float, Sphere, MeshDistortMaterial, Environment } from '@react-three/drei';
import * as THREE from 'three';

const FloatingBubble = ({
  position,
  color,
  scale = 1,
  speed = 1,
}: {
  position: [number, number, number];
  color: string;
  scale?: number;
  speed?: number;
}) => {
  const ref = useRef<THREE.Mesh>(null);

  useFrame((state) => {
    if (ref.current) {
      const t = state.clock.getElapsedTime();
      ref.current.position.y = position[1] + Math.sin(t * 0.5 * speed + position[0]) * 0.3;
      ref.current.rotation.x = t * 0.2;
      ref.current.rotation.z = t * 0.1;
    }
  });

  return (
    <Sphere ref={ref} args={[1, 64, 64]} position={position} scale={scale}>
      <MeshDistortMaterial
        color={color}
        envMapIntensity={1}
        clearcoat={0.8}
        clearcoatRoughness={0.2}
        metalness={0.1}
        roughness={0.3}
        distort={0.3}
        speed={2}
        transparent
        opacity={0.6}
      />
    </Sphere>
  );
};

export const HeroScene: React.FC = () => {
  return (
    <div className="absolute inset-0 z-0 opacity-100 pointer-events-none">
      <Canvas camera={{ position: [0, 0, 8], fov: 45 }}>
        <ambientLight intensity={0.8} />
        <pointLight position={[10, 10, 10]} intensity={1.5} color="#2563EB" />
        <pointLight position={[-10, -5, -10]} intensity={1.5} color="#2DD4BF" />

        <Float speed={1.5} rotationIntensity={0.2} floatIntensity={0.5}>
          {/* Main Blue Shape - right side */}
          <FloatingBubble position={[3.5, 0.5, -1]} color="#3B82F6" scale={1.5} speed={1} />
          {/* Secondary Mint Shape - left side */}
          <FloatingBubble position={[-3, 0.8, -2]} color="#2DD4BF" scale={1.2} speed={1.2} />
          {/* Distant Shape - bottom */}
          <FloatingBubble position={[0.5, -2.5, -4]} color="#60A5FA" scale={1.6} speed={0.8} />
          {/* Small accent bubble - top right */}
          <FloatingBubble position={[4, 2.5, -3]} color="#93C5FD" scale={0.7} speed={1.5} />
          {/* Small accent bubble - bottom left */}
          <FloatingBubble position={[-4, -1.5, -3]} color="#5EEAD4" scale={0.6} speed={1.3} />
        </Float>

        <Environment preset="city" />
      </Canvas>
    </div>
  );
};
