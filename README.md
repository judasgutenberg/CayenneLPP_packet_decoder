# CayenneLPP Packet Decoder

This script can be used to extract usable data from a CaynneLPP packet.  Read about CayenneLPP here:
https://www.thethingsnetwork.org/docs/devices/arduino/api/cayennelpp.html

Basically, CayenneLPP has a known lookup table of data types that it refers to in one-byte descriptors that then tell the parser the size of the data and how to decode it.
