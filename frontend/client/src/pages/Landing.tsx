/**
 * Landing Page - NytroAI-style landing with floating bubbles background
 * Split into hero with login/signup dialog triggers
 */
import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { LoginDialog } from '../components/LoginDialog';
import { Eye, EyeOff, AlertCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const LANDING_BG = "https://private-us-east-1.manuscdn.com/sessionFile/T1IqexwH1sdtw9FAEEvbN3/sandbox/Er8j99SpABFfjVR8NMypEk-img-3_1770590226000_na1fn_bGFuZGluZy1iZw.png?x-oss-process=image/resize,w_1920,h_1920/format,webp/quality,q_80&Expires=1798761600&Policy=eyJTdGF0ZW1lbnQiOlt7IlJlc291cmNlIjoiaHR0cHM6Ly9wcml2YXRlLXVzLWVhc3QtMS5tYW51c2Nkbi5jb20vc2Vzc2lvbkZpbGUvVDFJcWV4d0gxc2R0dzlGQUVFdmJOMy9zYW5kYm94L0VyOGo5OVNwQUJGZmpWUjhOTXlwRWstaW1nLTNfMTc3MDU5MDIyNjAwMF9uYTFmbl9iR0Z1WkdsdVp5MWlady5wbmc~eC1vc3MtcHJvY2Vzcz1pbWFnZS9yZXNpemUsd18xOTIwLGhfMTkyMC9mb3JtYXQsd2VicC9xdWFsaXR5LHFfODAiLCJDb25kaXRpb24iOnsiRGF0ZUxlc3NUaGFuIjp7IkFXUzpFcG9jaFRpbWUiOjE3OTg3NjE2MDB9fX1dfQ__&Key-Pair-Id=K2HSFNDJXOU9YS&Signature=jbxkXLmnunTJSsGdiawafavSYjd49jEZJsARw6ZUILsdSWCKoDVjumNGhWv0ElvZ8iOLpUcXA5UuEqxPqOHkIio5qE-f~vv3441wAFHnQmGEj2aaXGTFx3nrO0yrDkxJoWQyK2UUzjNKFLFgen7bTYxH7px6KnlkWz6S5p91LFybp~sVgmYAon-U-W6mxPpI1QmMJiDkhWQ7TkUSZbFTDXbTk66p62uq3pzJeYYFUOvnHROSzIZ6kuCgAciSoP5ZhFFA4FUlVGfmcDvcR0LnADqkxeHMKXGUpFTrTmEThqfbW472JTaXTjmia59G~rpImY49KW8ALyt0vajzMGqjBw__";

export default function Landing() {
  const [loginOpen, setLoginOpen] = useState(false);

  return (
    <div className="min-h-screen bg-white text-[#1e293b] font-sans overflow-hidden">
      {/* Nytro Logo - Top Left */}
      <div className="absolute top-6 left-6 z-20 animate-fade-in">
        <div className="flex items-center gap-3">
          <div className="w-11 h-11 rounded-xl bg-gradient-nytro flex items-center justify-center">
            <span className="text-white font-bold text-lg font-heading">N</span>
          </div>
          <div>
            <h1 className="font-heading font-bold text-[#1e293b] text-xl leading-tight">KeyLMS</h1>
            <p className="text-[10px] text-[#94a3b8] font-medium tracking-wider uppercase">Nytro Powered</p>
          </div>
        </div>
      </div>

      {/* Full Screen Hero */}
      <div className="relative min-h-screen flex items-center justify-center">
        {/* Background image */}
        <div
          className="absolute inset-0 z-0 bg-cover bg-center"
          style={{ backgroundImage: `url(${LANDING_BG})` }}
        />
        {/* Subtle overlay */}
        <div className="absolute inset-0 z-0 bg-gradient-to-br from-white/70 via-transparent to-white/50 pointer-events-none" />

        {/* Main Content */}
        <div className="relative z-10 flex flex-col items-center justify-center px-6 py-12">
          <h1 className="font-heading font-bold text-3xl md:text-5xl leading-tight mb-4 text-[#1e293b] text-center max-w-2xl animate-fade-in-up">
            Learning Management Made Simple
          </h1>

          <p className="text-[#64748b] text-lg md:text-xl text-center max-w-xl mb-10 animate-fade-in-up" style={{ animationDelay: '0.1s' }}>
            AI-powered learning and compliance management for training organizations
          </p>

          {/* Auth Buttons */}
          <div className="flex flex-col sm:flex-row gap-4 animate-fade-in-up" style={{ animationDelay: '0.2s' }}>
            <button
              onClick={() => setLoginOpen(true)}
              className="px-10 py-4 bg-gradient-nytro text-white rounded-full font-semibold shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300 text-lg min-w-[160px]"
            >
              Login
            </button>
            <button
              onClick={() => setLoginOpen(true)}
              className="px-10 py-4 bg-white text-[#64748b] border-2 border-[#e2e8f0] rounded-full font-semibold hover:border-[#3b82f6] hover:text-[#3b82f6] hover:scale-105 transition-all duration-300 text-lg min-w-[160px]"
            >
              Sign Up
            </button>
          </div>

          {/* Stats */}
          <div className="flex gap-8 mt-12 animate-fade-in" style={{ animationDelay: '0.3s' }}>
            <div className="text-center">
              <p className="text-2xl font-bold text-[#1e293b] font-heading">500+</p>
              <p className="text-sm text-[#94a3b8]">RTOs Using KeyLMS</p>
            </div>
            <div className="text-center">
              <p className="text-2xl font-bold text-[#1e293b] font-heading">50K+</p>
              <p className="text-sm text-[#94a3b8]">Students Managed</p>
            </div>
            <div className="text-center">
              <p className="text-2xl font-bold text-[#1e293b] font-heading">500K+</p>
              <p className="text-sm text-[#94a3b8]">Assessments Completed</p>
            </div>
          </div>

          {/* Footer */}
          <p className="mt-16 text-sm text-[#94a3b8] animate-fade-in" style={{ animationDelay: '0.4s' }}>
            &copy; 2026 Key Company. Powered by Nytro AI.
          </p>
        </div>
      </div>

      {/* Login Dialog */}
      <LoginDialog open={loginOpen} onOpenChange={setLoginOpen} />
    </div>
  );
}
