/**
 * Landing Page - NytroAI-style landing with 3D floating bubbles background
 * Uses react-three-fiber HeroScene matching NytroAI's QuantumScene
 */
import React, { useState } from 'react';
import { HeroScene } from '../components/HeroScene';
import { LoginDialog } from '../components/LoginDialog';

export default function Landing() {
  const [loginOpen, setLoginOpen] = useState(false);

  return (
    <div className="min-h-screen bg-white text-slate-900 selection:bg-teal-200 selection:text-teal-900 font-sans overflow-hidden">
      {/* Nytro Logo - Top Left */}
      <div className="absolute top-6 left-6 z-20 animate-fade-in">
        <div className="flex items-center gap-3">
          <div className="w-11 h-11 rounded-xl bg-gradient-nytro flex items-center justify-center">
            <span className="text-white font-bold text-lg font-heading">N</span>
          </div>
          <div>
            <h1 className="font-heading font-bold text-slate-900 text-xl leading-tight">NytroLMS</h1>
            <p className="text-[10px] text-slate-400 font-medium tracking-wider uppercase">Nytro Powered</p>
          </div>
        </div>
      </div>

      {/* Full Screen Hero */}
      <div className="relative min-h-screen flex items-center justify-center">
        {/* 3D Background Bubbles */}
        <HeroScene />

        {/* Subtle gradient overlay */}
        <div className="absolute inset-0 z-[1] bg-gradient-to-b from-white/90 via-white/50 to-white/80 pointer-events-none" />

        {/* Main Content */}
        <div className="relative z-10 flex flex-col items-center justify-center px-6 py-12">
          {/* Tagline */}
          <h1 className="font-heading font-bold text-3xl md:text-5xl leading-tight mb-4 text-slate-900 text-center max-w-2xl animate-fade-in-up">
            Learning Management Made Simple
          </h1>

          <p className="text-slate-600 text-lg md:text-xl text-center max-w-xl mb-10 animate-fade-in-up" style={{ animationDelay: '0.1s' }}>
            AI-powered learning and compliance management for training organizations
          </p>

          {/* Auth Buttons */}
          <div className="flex flex-col sm:flex-row gap-4 animate-fade-in-up" style={{ animationDelay: '0.2s' }}>
            <button
              onClick={() => setLoginOpen(true)}
              className="px-10 py-4 bg-gradient-to-r from-[#0d9488] to-[#3b82f6] text-white rounded-full font-semibold shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300 text-lg min-w-[160px]"
            >
              Login
            </button>
            <button
              onClick={() => setLoginOpen(true)}
              className="px-10 py-4 bg-white text-slate-700 border-2 border-slate-200 rounded-full font-semibold hover:border-[#3b82f6] hover:text-[#3b82f6] hover:scale-105 transition-all duration-300 text-lg min-w-[160px]"
            >
              Sign Up
            </button>
          </div>

          {/* Stats */}
          <div className="flex gap-8 mt-12 animate-fade-in" style={{ animationDelay: '0.3s' }}>
            <div className="text-center">
              <p className="text-2xl font-bold text-slate-900 font-heading">500+</p>
              <p className="text-sm text-slate-400">RTOs Using NytroLMS</p>
            </div>
            <div className="text-center">
              <p className="text-2xl font-bold text-slate-900 font-heading">50K+</p>
              <p className="text-sm text-slate-400">Students Managed</p>
            </div>
            <div className="text-center">
              <p className="text-2xl font-bold text-slate-900 font-heading">500K+</p>
              <p className="text-sm text-slate-400">Assessments Completed</p>
            </div>
          </div>

          {/* Footer */}
          <p className="mt-16 text-sm text-slate-400 animate-fade-in" style={{ animationDelay: '0.4s' }}>
            &copy; 2026 Key Company. Powered by Nytro AI.
          </p>
        </div>
      </div>

      {/* Login Dialog */}
      <LoginDialog open={loginOpen} onOpenChange={setLoginOpen} />
    </div>
  );
}
